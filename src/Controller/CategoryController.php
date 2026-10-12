<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories', name: 'api_categories_')]
class CategoryController extends AbstractController
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    /**
     * GET /api/categories
     * Returns all categories with their product count.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $rows = $this->categoryRepository->findAllWithProductCount();

        $data = array_map(function (array $row) {
            $category = $row[0];
            return [
                'id'           => $category->getId(),
                'name'         => $category->getName(),
                'slug'         => $category->getSlug(),
                'description'  => $category->getDescription(),
                'product_count' => (int) $row['productCount'],
            ];
        }, $rows);

        return $this->json([
            'data' => $data,
            'meta' => ['total' => count($data)],
        ]);
    }
}
