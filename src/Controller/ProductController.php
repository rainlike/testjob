<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/product', name: 'api_product_')]
class ProductController extends AbstractController
{
    private ProductRepository $repository;
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em,
        ProductRepository $repository
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
                ['status' => "Product with name {$data['name']} already exists!"],
                HttpResponse::HTTP_FOUND
            );
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setName($data['price']);
        if ($data['categoryName']) {
            /** @var Category $category */
            $category = $this->em->getRepository(Category::class)->findOneBy(['name' => $data['categoryName']]);
            if ($category) {
                $product->setCategory($category);
            }
        }

        $this->em->persist($product);
        $this->em->flush();

        return new JsonResponse(['status' => 'Product created!'], HttpResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get($id): JsonResponse
    {
        $product = $this->repository->findOneBy(['id' => $id]);
        if (!$product) {
            return new JsonResponse(
                ['status' => "Product not found!"],
                HttpResponse::HTTP_NOT_FOUND
            );
        }

        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'category' => $product->getCategory()->getName() ?? null,
        ];

        return new JsonResponse($data, HttpResponse::HTTP_OK);
    }

    #[Route('/', name: 'get_all', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        $products = $this->repository->findAll();
        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'category' => $product->getCategory()->getName() ?? null,
            ];
        }

        return new JsonResponse($data, HttpResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update($id, Request $request): JsonResponse
    {
        $product = $this->repository->findOneBy(['id' => $id]);
        if (!$product) {
            return new JsonResponse(
                ['status' => "Product not found!"],
                HttpResponse::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);
        foreach ($data as $field => $fieldData) {
            $setter = 'set'.ucfirst($field);
            $product->$setter($field);
        }

        $this->em->persist($product);
        $this->em->flush();

        return new JsonResponse(['status' => 'Product updated!'], HttpResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        $product = $this->repository->findOneBy(['id' => $id]);
        if (!$product) {
            return new JsonResponse(
                ['status' => "Product not found!"],
                HttpResponse::HTTP_NOT_FOUND
            );
        }

        $this->em->remove($product);
        $this->em->flush();

        return new JsonResponse(['status' => 'Product deleted!'], HttpResponse::HTTP_NO_CONTENT);
    }
}
