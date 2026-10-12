<?php

namespace App\Repository;

use App\Entity\Brand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Brand>
 */
class BrandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class);
    }

    /**
     * Get all brands with product count.
     *
     * @return array<array{brand: Brand, productCount: int}>
     */
    public function findAllWithProductCount(): array
    {
        return $this->createQueryBuilder('b')
            ->select('b', 'COUNT(p.id) AS productCount')
            ->leftJoin('b.products', 'p')
            ->groupBy('b.id')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
