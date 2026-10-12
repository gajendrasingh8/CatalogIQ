<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/products', name: 'api_products_')]
class ProductController extends AbstractController
{
    public function __construct(
        private ProductRepository  $productRepository,
        private SerializerInterface $serializer
    ) {}

    /**
     * GET /api/products
     * Browse all products (no search term) with filters, sort, pagination.
     *
     * Query params:
     *   page         int     default 1
     *   limit        int     default 20
     *   sort         string  relevance|price_asc|price_desc|newest|rating
     *   category_id  int[]
     *   brand_id     int[]
     *   price_min    float
     *   price_max    float
     *   rating_min   float
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $sort  = $request->query->get('sort', 'newest');

        $filters = $this->extractFilters($request);

        $result = $this->productRepository->searchWithTiming(
            query: '',
            page: $page,
            limit: $limit,
            sort: $sort,
            filters: $filters
        );

        return $this->buildResponse($result, $page, $limit);
    }

    /**
     * GET /api/products/{slug}
     * Single product detail.
     */
    #[Route('/{slug}', name: 'detail', methods: ['GET'])]
    public function detail(string $slug): JsonResponse
    {
        $product = $this->productRepository->findOneBy(['slug' => $slug]);

        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        // Serialize with the detail group
        $json = $this->serializer->serialize($product, 'json', [
            'groups' => ['product:detail'],
        ]);

        return new JsonResponse($json, 200, [], true);
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

    private function buildResponse(array $result, int $page, int $limit): JsonResponse
    {
        $total = $result['total'];
        $pages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        // Serialize product objects using serializer groups
        $productsJson = $this->serializer->serialize(
            $result['results'],
            'json',
            ['groups' => ['product:list']]
        );

        $response = [
            'data' => json_decode($productsJson, true),
            'meta' => [
                'total'   => $total,
                'page'    => $page,
                'limit'   => $limit,
                'pages'   => $pages,
            ],
            'timing' => [
                'mysql_ms' => $result['time_ms'],
                'es_ms'    => null, // Phase 2
            ],
        ];

        return $this->json($response);
    }
}
