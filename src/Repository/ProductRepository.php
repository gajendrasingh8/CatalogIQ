<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * MySQL FULLTEXT search with raw SQL (avoids DQL MATCH AGAINST limitations).
     * Returns paginated Product entities + timing info.
     *
     * @return array{results: Product[], total: int, time_ms: float, method: string}
     */
    public function searchWithTiming(
        string $query,
        int $page = 1,
        int $limit = 20,
        ?string $sort = 'relevance',
        array $filters = []
    ): array {
        $startTime = microtime(true);
        $conn      = $this->getEntityManager()->getConnection();

        // --- Build WHERE conditions ---
        $where  = [];
        $params = [];
        $types  = [];
        $method = 'BROWSE';

        if (!empty($query)) {
            $where[]          = 'MATCH(p.name, p.description) AGAINST(:query IN BOOLEAN MODE)';
            $params['query']  = $query;
            $method           = 'FULLTEXT MATCH';
        }

        if (!empty($filters['category_id'])) {
            $ids = array_map('intval', (array) $filters['category_id']);
            $where[] = 'p.category_id IN (' . implode(',', $ids) . ')';
        }

        if (!empty($filters['brand_id'])) {
            $ids = array_map('intval', (array) $filters['brand_id']);
            $where[] = 'p.brand_id IN (' . implode(',', $ids) . ')';
        }

        if (isset($filters['price_min'])) {
            $where[]              = 'p.price >= :priceMin';
            $params['priceMin']   = $filters['price_min'];
        }

        if (isset($filters['price_max'])) {
            $where[]              = 'p.price <= :priceMax';
            $params['priceMax']   = $filters['price_max'];
        }

        if (isset($filters['rating_min'])) {
            $where[]              = 'p.rating >= :ratingMin';
            $params['ratingMin']  = $filters['rating_min'];
        }

        $whereClause = empty($where) ? '1=1' : implode(' AND ', $where);

        // --- ORDER BY ---
        $orderBy = match ($sort) {
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'newest'     => 'p.created_at DESC',
            'rating'     => 'p.rating DESC',
            default      => !empty($query)
                ? 'MATCH(p.name, p.description) AGAINST(:query IN BOOLEAN MODE) DESC'
                : 'p.id ASC',
        };

        $offset = ($page - 1) * $limit;

        // --- Count total ---
        $countSql = "SELECT COUNT(p.id) FROM products p WHERE {$whereClause}";
        $total    = (int) $conn->executeQuery($countSql, $params)->fetchOne();

        // --- Fetch IDs for this page ---
        // NOTE: LIMIT and OFFSET must be inlined as integers — MySQL rejects quoted
        // string bindings ('20') for these clauses (SQLSTATE[42000]).
        $limitInt  = (int) $limit;
        $offsetInt = (int) $offset;
        $idSql = "
            SELECT p.id
            FROM products p
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT {$limitInt} OFFSET {$offsetInt}
        ";

        $ids = $conn->executeQuery($idSql, $params)->fetchFirstColumn();

        // --- Load full Product entities in original order ---
        $results = [];
        if (!empty($ids)) {
            $entityMap = [];
            $entities  = $this->findBy(['id' => $ids]);
            foreach ($entities as $entity) {
                $entityMap[$entity->getId()] = $entity;
            }
            // Preserve the SQL ORDER BY order
            foreach ($ids as $id) {
                if (isset($entityMap[$id])) {
                    $results[] = $entityMap[$id];
                }
            }
        }

        $endTime = microtime(true);
        $timeMs  = round(($endTime - $startTime) * 1000, 2);

        return [
            'results' => $results,
            'total'   => $total,
            'time_ms' => $timeMs,
            'method'  => $method,
        ];
    }

    /**
     * Simple LIKE search (fallback / comparison).
     *
     * @return array{results: array, total: int, time_ms: float, method: string}
     */
    public function searchWithLike(
        string $query,
        int $page = 1,
        int $limit = 20
    ): array {
        $startTime = microtime(true);

        $qb = $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->join('p.brand', 'b');

        if (!empty($query)) {
            $qb->where('p.name LIKE :query OR p.description LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb);
        $total = count($paginator);
        $results = iterator_to_array($paginator);

        $endTime = microtime(true);
        $timeMs = round(($endTime - $startTime) * 1000, 2);

        return [
            'results' => $results,
            'total'   => $total,
            'time_ms' => $timeMs,
            'method'  => 'LIKE %query%',
        ];
    }

    /**
     * Get facet counts from MySQL for categories, brands, price ranges, and ratings.
     */
    public function getFacets(string $query = '', array $filters = []): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $whereClause = '1=1';
        $params = [];

        if (!empty($query)) {
            $whereClause .= " AND MATCH(p.name, p.description) AGAINST(:query IN BOOLEAN MODE)";
            $params['query'] = $query;
        }

        // Category facets
        $categorySql = "
            SELECT c.id, c.name, c.slug, COUNT(p.id) as doc_count
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE {$whereClause}
            GROUP BY c.id, c.name, c.slug
            ORDER BY doc_count DESC
        ";
        $categories = $conn->executeQuery($categorySql, $params)->fetchAllAssociative();

        // Brand facets
        $brandSql = "
            SELECT b.id, b.name, b.slug, COUNT(p.id) as doc_count
            FROM products p
            JOIN brands b ON p.brand_id = b.id
            WHERE {$whereClause}
            GROUP BY b.id, b.name, b.slug
            ORDER BY doc_count DESC
        ";
        $brands = $conn->executeQuery($brandSql, $params)->fetchAllAssociative();

        // Price range facets
        $priceRangeSql = "
            SELECT
                CASE
                    WHEN p.price < 50 THEN 'Under $50'
                    WHEN p.price BETWEEN 50 AND 100 THEN '$50 - $100'
                    WHEN p.price BETWEEN 100 AND 250 THEN '$100 - $250'
                    WHEN p.price BETWEEN 250 AND 500 THEN '$250 - $500'
                    WHEN p.price BETWEEN 500 AND 1000 THEN '$500 - $1000'
                    ELSE 'Over $1000'
                END as price_range,
                CASE
                    WHEN p.price < 50 THEN 0
                    WHEN p.price BETWEEN 50 AND 100 THEN 50
                    WHEN p.price BETWEEN 100 AND 250 THEN 100
                    WHEN p.price BETWEEN 250 AND 500 THEN 250
                    WHEN p.price BETWEEN 500 AND 1000 THEN 500
                    ELSE 1000
                END as min_price,
                CASE
                    WHEN p.price < 50 THEN 50
                    WHEN p.price BETWEEN 50 AND 100 THEN 100
                    WHEN p.price BETWEEN 100 AND 250 THEN 250
                    WHEN p.price BETWEEN 250 AND 500 THEN 500
                    WHEN p.price BETWEEN 500 AND 1000 THEN 1000
                    ELSE 99999
                END as max_price,
                COUNT(p.id) as doc_count
            FROM products p
            WHERE {$whereClause}
            GROUP BY price_range, min_price, max_price
            ORDER BY min_price ASC
        ";
        $priceRanges = $conn->executeQuery($priceRangeSql, $params)->fetchAllAssociative();

        // Rating facets
        $ratingSql = "
            SELECT
                FLOOR(p.rating) as rating_floor,
                COUNT(p.id) as doc_count
            FROM products p
            WHERE {$whereClause}
            GROUP BY rating_floor
            ORDER BY rating_floor DESC
        ";
        $ratings = $conn->executeQuery($ratingSql, $params)->fetchAllAssociative();

        return [
            'categories'   => $categories,
            'brands'       => $brands,
            'price_ranges' => $priceRanges,
            'ratings'      => $ratings,
        ];
    }

    /**
     * Autocomplete suggestions: returns top product names and category names
     * matching the query prefix.
     *
     * @return array{products: array, categories: array}
     */
    public function autocomplete(string $query, int $limit = 8): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $like = '%' . $query . '%';

        // Product name suggestions
        $limitInt   = (int) $limit;
        $productSql = "
            SELECT DISTINCT p.name, p.slug, p.image_url
            FROM products p
            WHERE p.name LIKE :like
            ORDER BY p.review_count DESC
            LIMIT {$limitInt}
        ";
        $products = $conn->executeQuery($productSql, ['like' => $like], [
            'like' => \Doctrine\DBAL\ParameterType::STRING,
        ])->fetchAllAssociative();

        // Category suggestions
        $categorySql = "
            SELECT c.name, c.slug
            FROM categories c
            WHERE c.name LIKE :like
            LIMIT 3
        ";
        $categories = $conn->executeQuery($categorySql, ['like' => $like], [
            'like' => \Doctrine\DBAL\ParameterType::STRING,
        ])->fetchAllAssociative();

        return [
            'products'   => $products,
            'categories' => $categories,
        ];
    }
}
