<?php

namespace App\Controller;

use App\Repository\BrandRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/brands', name: 'api_brands_')]
class BrandController extends AbstractController
{
    public function __construct(
        private BrandRepository $brandRepository
    ) {}

    /**
     * GET /api/brands
     * Returns all brands with their product count.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $rows = $this->brandRepository->findAllWithProductCount();

        $data = array_map(function (array $row) {
            $brand = $row[0];
            return [
                'id'            => $brand->getId(),
                'name'          => $brand->getName(),
                'slug'          => $brand->getSlug(),
                'logo_url'      => $brand->getLogoUrl(),
                'product_count' => (int) $row['productCount'],
            ];
        }, $rows);

        return $this->json([
            'data' => $data,
            'meta' => ['total' => count($data)],
        ]);
    }
}
