<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/category', name: 'api_category_')]
class CategoryController extends AbstractController
{
    private CategoryRepository $repository;
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em,
        CategoryRepository $repository
    )
    {
        $this->em = $em;
        $this->repository = $repository;
    }

    #[Route('/', name: 'add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data['name']) {
            throw new NotFoundHttpException('Expecting mandatory parameter - `name`!');
        }

        if ($this->repository->findOneBy(['name' => $data['name']])) {
            return new JsonResponse(
                ['status' => "Category with name {$data['name']} already exists!"],
                HttpResponse::HTTP_FOUND
            );
        }

        $category = new Category();
        $category->setName($data['name']);
        $this->em->persist($category);
        $this->em->flush();

        return new JsonResponse(['status' => 'Category created!'], HttpResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get($id): JsonResponse
    {
        $category = $this->repository->findOneBy(['id' => $id]);
        if (!$category) {
            return new JsonResponse(
                ['status' => "Category not found!"],
                HttpResponse::HTTP_NOT_FOUND
            );
        }

        $data = [
            'id' => $category->getId(),
            'name' => $category->getName(),
        ];

        return new JsonResponse($data, HttpResponse::HTTP_OK);
    }

    #[Route('/', name: 'get_all', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        $categories = $this->repository->findAll();
        $data = [];

        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ];
        }

        return new JsonResponse($data, HttpResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update($id, Request $request): JsonResponse
    {
        $category = $this->repository->findOneBy(['id' => $id]);
        if (!$category) {
            return new JsonResponse(
                ['status' => "Category not found!"],
                HttpResponse::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);
        if ($data['name']) {
            $category->setName($data['name']);
        }

        $this->em->persist($category);
        $this->em->flush();

        return new JsonResponse(['status' => 'Category updated!'], HttpResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        $category = $this->repository->findOneBy(['id' => $id]);
        if (!$category) {
            return new JsonResponse(
                ['status' => "Category not found!"],
                HttpResponse::HTTP_NOT_FOUND
            );
        }

        $this->em->remove($category);
        $this->em->flush();

        return new JsonResponse(['status' => 'Category deleted!'], HttpResponse::HTTP_NO_CONTENT);
    }
}
