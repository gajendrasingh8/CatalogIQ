<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Get all categories with product count.
     *
     * @return array<array{category: Category, productCount: int}>
     */
    public function findAllWithProductCount(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(p.id) AS productCount')
            ->leftJoin('c.products', 'p')
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
