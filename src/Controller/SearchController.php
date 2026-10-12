<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\ElasticsearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'api_search_')]
class SearchController extends AbstractController
{
    public function __construct(
        private ProductRepository     $productRepository,
        private SerializerInterface   $serializer,
        private ElasticsearchService  $elasticsearchService,
    ) {}

    /**
     * GET /api/search
     *
     * Main search endpoint. Runs the selected engine(s) and falls back to MySQL
     * when Elasticsearch is requested but unavailable or fails.
     *
     * Query params:
     *   q            string  search query
     *   page         int     default 1
     *   limit        int     default 20 (max 100)
     *   sort         string  relevance|price_asc|price_desc|newest|rating
     *   category_id  int[]   filter by category IDs
     *   brand_id     int[]   filter by brand IDs
     *   price_min    float
     *   price_max    float
     *   rating_min   float
     *   engine       string  mysql|es|both (default: both)
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query   = trim((string) $request->query->get('q', ''));
        $page    = max(1, (int) $request->query->get('page', 1));
        $limit   = min(100, max(1, (int) $request->query->get('limit', 20)));
        $sort    = $request->query->get('sort', 'relevance');
        $engine = (string) $request->query->get('engine', 'both'); // mysql | es | both
        if (!in_array($engine, ['mysql', 'es', 'both'], true)) {
            $engine = 'both';
        }

        $filters = $this->extractFilters($request);

        // ----------------------------------------------------------------
        // Run MySQL when explicitly selected or as part of Both.
        // ----------------------------------------------------------------
        $mysqlResult   = null;
        $mysqlTimeMs   = null;
        $mysqlMethod   = null;

        if ($engine !== 'es') {
            $mysqlResult = $this->productRepository->searchWithTiming(
                query:   $query,
                page:    $page,
                limit:   $limit,
                sort:    $sort,
                filters: $filters
            );
            $mysqlTimeMs = $mysqlResult['time_ms'];
            $mysqlMethod = $mysqlResult['method'];
        }

        // ----------------------------------------------------------------
        // Run Elasticsearch when explicitly selected or as part of Both.
        // ----------------------------------------------------------------
        $esResult     = null;
        $esTimeMs     = null;
        $esAvailable  = false;

        if ($engine !== 'mysql' && $this->elasticsearchService->isAvailable()) {
            $esAvailable = true;
            try {
                $esResult = $this->elasticsearchService->search(
                    query:   $query,
                    filters: $filters,
                    page:    $page,
                    limit:   $limit,
                    sort:    $sort
                );
                $esTimeMs = $esResult['time_ms'];
            } catch (\Throwable $e) {
                $esAvailable = false;
                $esTimeMs    = null;
            }
        }

        // Elasticsearch-only requests still return useful results during an outage.
        if ($mysqlResult === null && (!$esAvailable || $esResult === null)) {
            $mysqlResult = $this->productRepository->searchWithTiming(
                query:   $query,
                page:    $page,
                limit:   $limit,
                sort:    $sort,
                filters: $filters
            );
            $mysqlTimeMs = $mysqlResult['time_ms'];
            $mysqlMethod = $mysqlResult['method'];
        }

        // ----------------------------------------------------------------
        // Prefer Elasticsearch results when available; otherwise use MySQL.
        // ----------------------------------------------------------------
        $primaryResults = null;
        $primaryTotal   = 0;
        $facets         = [];
        $resultEngine   = 'mysql';

        if ($esAvailable && $esResult !== null) {
            // ES results are raw arrays with highlight data
            $primaryResults = $esResult['results'];
            $primaryTotal   = $esResult['total'];
            $facets         = $esResult['facets'];
            $resultEngine   = 'elasticsearch';
        } else {
            // Serialize MySQL Product entities
            if ($mysqlResult !== null) {
                $productsJson   = $this->serializer->serialize(
                    $mysqlResult['results'],
                    'json',
                    ['groups' => ['product:list']]
                );
                $primaryResults = json_decode($productsJson, true);
                $primaryTotal   = $mysqlResult['total'];
                $facets         = $this->productRepository->getFacets($query, $filters);
            }
        }

        $pages = $limit > 0 ? (int) ceil($primaryTotal / $limit) : 1;

        return $this->json([
            'data'   => $primaryResults ?? [],
            'meta'   => [
                'total'      => $primaryTotal,
                'page'       => $page,
                'limit'      => $limit,
                'pages'      => $pages,
                'query'      => $query,
                'sort'       => $sort,
                'engine'     => $resultEngine,
                'requested_engine' => $engine,
            ],
            'facets' => [
                'categories'   => $facets['categories'] ?? [],
                'brands'       => $facets['brands'] ?? [],
                'price_ranges' => $facets['price_ranges'] ?? [],
                'ratings'      => $facets['ratings'] ?? [],
            ],
            'timing' => [
                'mysql_ms'      => $mysqlTimeMs,
                'mysql_method'  => $mysqlMethod,
                'es_ms'         => $esTimeMs,
                'es_available'  => $esAvailable,
                'es_requested'  => $engine !== 'mysql',
            ],
        ]);
    }

    /**
     * GET /api/autocomplete
     *
     * Typeahead suggestions. Uses the selected engine, falling back to MySQL.
     * Returns up to 8 product names + 3 category names.
     *
     * Query params:
     *   q       string  (min 2 chars)
     *   engine  string  mysql|es|both (default: both)
     */
    #[Route('/autocomplete', name: 'autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $engine = (string) $request->query->get('engine', 'both');
        if (!in_array($engine, ['mysql', 'es', 'both'], true)) {
            $engine = 'both';
        }

        if (strlen($query) < 2) {
            return $this->json(['products' => [], 'categories' => []]);
        }

        // Use ES autocomplete unless MySQL was explicitly selected.
        if ($engine !== 'mysql' && $this->elasticsearchService->isAvailable()) {
            try {
                $suggestions = $this->elasticsearchService->autocomplete($query, 8);
                return $this->json($suggestions);
            } catch (\Throwable) {
                // Fall through to MySQL
            }
        }

        // Fallback: MySQL LIKE search
        $suggestions = $this->productRepository->autocomplete($query, 8);
        return $this->json($suggestions);
    }

    /**
     * GET /api/elastic/status
     *
     * Quick health check: is Elasticsearch reachable? Is the index created?
     */
    #[Route('/elastic/status', name: 'elastic_status', methods: ['GET'])]
    public function elasticStatus(): JsonResponse
    {
        $available   = $this->elasticsearchService->isAvailable();
        $indexExists = $available && $this->elasticsearchService->indexExists();

        return $this->json([
            'elasticsearch' => [
                'available'    => $available,
                'index_exists' => $indexExists,
                'index_name'   => ElasticsearchService::INDEX,
            ],
        ]);
    }

    // -------------------------------------------------------------------------

    private function extractFilters(Request $request): array
    {
        $filters = [];

        $categoryIds = $request->query->all('category_id');
        if (!empty($categoryIds)) {
            $filters['category_id'] = array_map('intval', (array) $categoryIds);
        }

        $brandIds = $request->query->all('brand_id');
        if (!empty($brandIds)) {
            $filters['brand_id'] = array_map('intval', (array) $brandIds);
        }

        if ($request->query->has('price_min')) {
            $filters['price_min'] = (float) $request->query->get('price_min');
        }

        if ($request->query->has('price_max')) {
            $filters['price_max'] = (float) $request->query->get('price_max');
        }

        if ($request->query->has('rating_min')) {
            $filters['rating_min'] = (float) $request->query->get('rating_min');
        }

        return $filters;
    }
}
