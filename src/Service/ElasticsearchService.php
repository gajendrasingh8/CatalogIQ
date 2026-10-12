<?php

namespace App\Service;

use App\Entity\Product;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

/**
 * ElasticsearchService
 *
 * Handles all Elasticsearch operations for CatalogIQ:
 *   - Index creation with custom analyzers (synonyms, edge-ngram autocomplete)
 *   - Single / bulk indexing of products
 *   - Full-text fuzzy search with filters, facets, sorting, pagination
 *   - Autocomplete / typeahead
 *   - Index deletion
 */
class ElasticsearchService
{
    public const INDEX = 'catalogiq_products';

    public function __construct(private readonly Client $client) {}

    // =========================================================================
    // Index Management
    // =========================================================================

    /**
     * Create the catalogiq_products index with custom analyzers.
     * Synonyms, edge-ngram autocomplete, and fuzzy-ready field mappings.
     *
     * @throws ClientResponseException|ServerResponseException|MissingParameterException
     */
    public function createIndex(): void
    {
        if ($this->indexExists()) {
            return;
        }

        $this->client->indices()->create([
            'index' => self::INDEX,
            'body'  => [
                'settings' => $this->buildIndexSettings(),
                'mappings' => $this->buildMappings(),
            ],
        ]);
    }

    /**
     * Delete the index (used for full re-index).
     */
    public function deleteIndex(): void
    {
        if ($this->indexExists()) {
            $this->client->indices()->delete(['index' => self::INDEX]);
        }
    }

    /**
     * Drop and recreate the index from scratch.
     */
    public function resetIndex(): void
    {
        $this->deleteIndex();
        $this->createIndex();
    }

    public function indexExists(): bool
    {
        try {
            $response = $this->client->indices()->exists(['index' => self::INDEX]);
            return $response->getStatusCode() === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    // =========================================================================
    // Indexing
    // =========================================================================

    /**
     * Index a single product document.
     */
    public function indexProduct(Product $product): void
    {
        $this->client->index([
            'index' => self::INDEX,
            'id'    => (string) $product->getId(),
            'body'  => $product->toElasticDocument(),
        ]);
    }

    /**
     * Bulk-index an array of Product entities.
     * Sends documents in batches for performance.
     *
     * @param Product[] $products
     * @param callable|null $onBatch  Called after each batch with (indexed, total)
     */
    public function bulkIndex(array $products, ?callable $onBatch = null): int
    {
        $total    = count($products);
        $indexed  = 0;
        $batchSize = 100;

        foreach (array_chunk($products, $batchSize) as $batch) {
            $params = ['body' => []];

            foreach ($batch as $product) {
                $params['body'][] = [
                    'index' => [
                        '_index' => self::INDEX,
                        '_id'    => (string) $product->getId(),
                    ],
                ];
                $params['body'][] = $product->toElasticDocument();
            }

            $this->client->bulk($params);
            $indexed += count($batch);

            if ($onBatch !== null) {
                $onBatch($indexed, $total);
            }
        }

        // Refresh so docs are immediately searchable
        $this->client->indices()->refresh(['index' => self::INDEX]);

        return $indexed;
    }

    // =========================================================================
    // Search
    // =========================================================================

    /**
     * Full-text search with filters, facets, sort, pagination, and Elasticsearch execution timing.
     *
     * @param string $query     Search string (empty = match-all)
     * @param array  $filters   ['category_id'=>[], 'brand_id'=>[], 'price_min', 'price_max', 'rating_min']
     * @param int    $page      1-indexed
     * @param int    $limit     Results per page
     * @param string $sort      relevance|price_asc|price_desc|newest|rating
     *
     * @return array{results: array, total: int, time_ms: ?int, facets: array}
     */
    public function search(
        string $query,
        array  $filters = [],
        int    $page = 1,
        int    $limit = 20,
        string $sort = 'relevance'
    ): array {
        $from = ($page - 1) * $limit;

        $must   = [];
        $filter = [];

        // --- Query clause ---
        if (!empty($query)) {
            $must[] = [
                'multi_match' => [
                    'query'     => $query,
                    'fields'    => ['name^3', 'tags^2', 'description', 'category_name', 'brand_name'],
                    'type'      => 'best_fields',
                    'fuzziness' => 'AUTO',
                    'analyzer'  => 'search_analyzer',
                ],
            ];
        } else {
            $must[] = ['match_all' => (object) []];
        }

        // --- Filters ---
        if (!empty($filters['category_id'])) {
            $filter[] = ['terms' => ['category_id' => array_map('intval', (array) $filters['category_id'])]];
        }

        if (!empty($filters['brand_id'])) {
            $filter[] = ['terms' => ['brand_id' => array_map('intval', (array) $filters['brand_id'])]];
        }

        if (isset($filters['price_min']) || isset($filters['price_max'])) {
            $range = [];
            if (isset($filters['price_min'])) {
                $range['gte'] = (float) $filters['price_min'];
            }
            if (isset($filters['price_max'])) {
                $range['lte'] = (float) $filters['price_max'];
            }
            $filter[] = ['range' => ['price' => $range]];
        }

        if (isset($filters['rating_min'])) {
            $filter[] = ['range' => ['rating' => ['gte' => (float) $filters['rating_min']]]];
        }

        // --- Sorting ---
        $sortClause = $this->buildSortClause($sort, !empty($query));

        // --- Aggregations (facets) ---
        $aggs = $this->buildAggregations();

        // --- Build query ---
        $body = [
            'from'  => $from,
            'size'  => $limit,
            'query' => [
                'bool' => array_filter([
                    'must'   => $must,
                    'filter' => $filter ?: null,
                ]),
            ],
            'sort'  => $sortClause,
            'aggs'  => $aggs,
            'highlight' => [
                'fields' => [
                    'name'        => ['number_of_fragments' => 0],
                    'description' => ['fragment_size' => 150, 'number_of_fragments' => 1],
                ],
                'pre_tags'  => ['<em>'],
                'post_tags' => ['</em>'],
            ],
        ];

        $response = $this->client->search([
            'index' => self::INDEX,
            'body'  => $body,
        ]);

        $raw     = $response->asArray();
        // `took` is Elasticsearch's own execution time in milliseconds. Unlike
        // a PHP stopwatch, it excludes query construction and the HTTP round trip.
        $timeMs  = isset($raw['took']) ? (int) $raw['took'] : null;
        $hits    = $raw['hits']['hits'] ?? [];
        $total   = $raw['hits']['total']['value'] ?? 0;
        $aggData = $raw['aggregations'] ?? [];

        // Map hits → plain arrays (with highlight)
        $results = array_map(function (array $hit): array {
            $source = $hit['_source'];
            $source['_highlight'] = $hit['highlight'] ?? [];
            return $source;
        }, $hits);

        return [
            'results' => $results,
            'total'   => $total,
            'time_ms' => $timeMs,
            'facets'  => $this->parseAggregations($aggData),
        ];
    }

    // =========================================================================
    // Autocomplete
    // =========================================================================

    /**
     * Typeahead suggestions using edge-ngram analyzer.
     * Returns up to $limit product suggestions + 3 category suggestions.
     *
     * @return array{products: array, categories: array}
     */
    public function autocomplete(string $query, int $limit = 8): array
    {
        $startTime = microtime(true);

        $response = $this->client->search([
            'index' => self::INDEX,
            'body'  => [
                'size'  => $limit,
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'match' => [
                                    'name.autocomplete' => [
                                        'query'    => $query,
                                        'analyzer' => 'search_analyzer',
                                    ],
                                ],
                            ],
                            [
                                'match_phrase_prefix' => [
                                    'name' => [
                                        'query'          => $query,
                                        'max_expansions' => 10,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '_source' => ['name', 'slug', 'image_url', 'price', 'rating'],
                'sort'    => [['review_count' => ['order' => 'desc']]],
            ],
        ]);

        $raw  = $response->asArray();
        $hits = $raw['hits']['hits'] ?? [];

        $products = array_map(fn (array $h) => $h['_source'], $hits);

        // Category suggestions via terms aggregation
        $catResponse = $this->client->search([
            'index' => self::INDEX,
            'body'  => [
                'size'  => 0,
                'query' => [
                    'wildcard' => [
                        'category_name.keyword' => ['value' => '*' . strtolower($query) . '*', 'case_insensitive' => true],
                    ],
                ],
                'aggs' => [
                    'categories' => [
                        'terms' => ['field' => 'category_name.keyword', 'size' => 3],
                    ],
                    'category_slugs' => [
                        'terms' => ['field' => 'category_slug.keyword', 'size' => 3],
                    ],
                ],
            ],
        ]);

        $catRaw    = $catResponse->asArray();
        $catBuckets = $catRaw['aggregations']['categories']['buckets'] ?? [];

        $categories = array_map(fn (array $b) => [
            'name' => $b['key'],
            'slug' => '',
        ], $catBuckets);

        return [
            'products'   => $products,
            'categories' => $categories,
            'time_ms'    => round((microtime(true) - $startTime) * 1000, 2),
        ];
    }

    // =========================================================================
    // Health Check
    // =========================================================================

    /**
     * Returns true if Elasticsearch is reachable.
     */
    public function isAvailable(): bool
    {
        try {
            $this->client->ping();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function buildIndexSettings(): array
    {
        return [
            'number_of_shards'   => 1,
            'number_of_replicas' => 0,
            'analysis'           => [
                'filter' => [
                    'synonym_filter' => [
                        'type'     => 'synonym',
                        'synonyms' => [
                            'mobile, phone, smartphone',
                            'sneakers, shoes, footwear',
                            'laptop, notebook, computer',
                            'earphones, earbuds, headphones',
                            'tv, television',
                            'fridge, refrigerator',
                            'sofa, couch',
                        ],
                    ],
                    'autocomplete_filter' => [
                        'type'     => 'edge_ngram',
                        'min_gram' => 2,
                        'max_gram' => 20,
                    ],
                ],
                'analyzer' => [
                    'search_analyzer' => [
                        'type'      => 'custom',
                        'tokenizer' => 'standard',
                        'filter'    => ['lowercase', 'synonym_filter'],
                    ],
                    'autocomplete_analyzer' => [
                        'type'      => 'custom',
                        'tokenizer' => 'standard',
                        'filter'    => ['lowercase', 'autocomplete_filter'],
                    ],
                ],
            ],
        ];
    }

    private function buildMappings(): array
    {
        return [
            'properties' => [
                'id'           => ['type' => 'integer'],
                'name'         => [
                    'type'     => 'text',
                    'analyzer' => 'search_analyzer',
                    'fields'   => [
                        'keyword'      => ['type' => 'keyword'],
                        'autocomplete' => [
                            'type'            => 'text',
                            'analyzer'        => 'autocomplete_analyzer',
                            'search_analyzer' => 'search_analyzer',
                        ],
                    ],
                ],
                'slug'          => ['type' => 'keyword'],
                'description'   => ['type' => 'text', 'analyzer' => 'search_analyzer'],
                'price'         => ['type' => 'float'],
                'original_price'=> ['type' => 'float'],
                'rating'        => ['type' => 'float'],
                'review_count'  => ['type' => 'integer'],
                'image_url'     => ['type' => 'keyword', 'index' => false],
                'stock_status'  => ['type' => 'keyword'],
                'sku'           => ['type' => 'keyword'],
                'tags'          => ['type' => 'text', 'analyzer' => 'search_analyzer', 'fields' => ['keyword' => ['type' => 'keyword']]],
                'category_id'   => ['type' => 'integer'],
                'category_name' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                'category_slug' => ['type' => 'keyword', 'fields' => ['keyword' => ['type' => 'keyword']]],
                'brand_id'      => ['type' => 'integer'],
                'brand_name'    => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                'brand_slug'    => ['type' => 'keyword', 'fields' => ['keyword' => ['type' => 'keyword']]],
                'created_at'    => ['type' => 'date', 'format' => "yyyy-MM-dd'T'HH:mm:ss"],
                'updated_at'    => ['type' => 'date', 'format' => "yyyy-MM-dd'T'HH:mm:ss"],
                'specifications'=> ['type' => 'object', 'enabled' => false],
            ],
        ];
    }

    private function buildSortClause(string $sort, bool $hasQuery): array
    {
        return match ($sort) {
            'price_asc'  => [['price' => ['order' => 'asc']]],
            'price_desc' => [['price' => ['order' => 'desc']]],
            'newest'     => [['created_at' => ['order' => 'desc']]],
            'rating'     => [['rating' => ['order' => 'desc']]],
            default      => $hasQuery ? [['_score' => ['order' => 'desc']]] : [['review_count' => ['order' => 'desc']]],
        };
    }

    private function buildAggregations(): array
    {
        return [
            'categories' => [
                'terms' => ['field' => 'category_name.keyword', 'size' => 50, 'order' => ['_count' => 'desc']],
                'aggs'  => ['category_id' => ['terms' => ['field' => 'category_id', 'size' => 1]]],
            ],
            'brands' => [
                'terms' => ['field' => 'brand_name.keyword', 'size' => 50, 'order' => ['_count' => 'desc']],
                'aggs'  => ['brand_id' => ['terms' => ['field' => 'brand_id', 'size' => 1]]],
            ],
            'price_stats' => [
                'stats' => ['field' => 'price'],
            ],
            'price_ranges' => [
                'range' => [
                    'field'  => 'price',
                    'ranges' => [
                        ['key' => 'Under $50',     'to'   => 50],
                        ['key' => '$50 - $100',    'from' => 50,  'to'  => 100],
                        ['key' => '$100 - $250',   'from' => 100, 'to'  => 250],
                        ['key' => '$250 - $500',   'from' => 250, 'to'  => 500],
                        ['key' => '$500 - $1000',  'from' => 500, 'to'  => 1000],
                        ['key' => 'Over $1000',    'from' => 1000],
                    ],
                ],
            ],
            'ratings' => [
                'terms' => ['field' => 'rating', 'size' => 10, 'order' => ['_key' => 'desc']],
            ],
        ];
    }

    private function parseAggregations(array $aggs): array
    {
        // Categories
        $categories = array_map(function (array $bucket): array {
            $idBuckets = $bucket['category_id']['buckets'] ?? [];
            return [
                'name'      => $bucket['key'],
                'doc_count' => $bucket['doc_count'],
                'id'        => $idBuckets[0]['key'] ?? null,
            ];
        }, $aggs['categories']['buckets'] ?? []);

        // Brands
        $brands = array_map(function (array $bucket): array {
            $idBuckets = $bucket['brand_id']['buckets'] ?? [];
            return [
                'name'      => $bucket['key'],
                'doc_count' => $bucket['doc_count'],
                'id'        => $idBuckets[0]['key'] ?? null,
            ];
        }, $aggs['brands']['buckets'] ?? []);

        // Price ranges
        $priceRanges = array_map(fn (array $b) => [
            'price_range' => $b['key'],
            'doc_count'   => $b['doc_count'],
            'min_price'   => $b['from'] ?? 0,
            'max_price'   => $b['to'] ?? 99999,
        ], $aggs['price_ranges']['buckets'] ?? []);

        // Ratings
        $ratings = array_map(fn (array $b) => [
            'rating_floor' => (int) $b['key'],
            'doc_count'    => $b['doc_count'],
        ], $aggs['ratings']['buckets'] ?? []);

        return [
            'categories'   => $categories,
            'brands'       => $brands,
            'price_ranges' => $priceRanges,
            'ratings'      => $ratings,
        ];
    }
}
