<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'product:import')]
class ProductImportCommand extends Command
{
    private $entityManager;

    protected ?string $path = null;
    protected const DEFAULT_PATH = './imports/products.json';

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED,
                'Path to file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Import Products',
            '============',
            '',
        ]);

        $em = $this->entityManager;
        $repository = $em->getRepository(Product::class);
        $categoryRepository = $em->getRepository(Category::class);
        $path = $input->getOption('path') ?: self::DEFAULT_PATH;

        if (!file_exists($path)) {
            $output->writeln('File not found!');
            return Command::FAILURE;
        }
        $fileContent = file_get_contents($path);
        $fileDecodedContent = json_decode($fileContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $output->writeln('File must be in JSON format!');
            return Command::FAILURE;
        }

        foreach ($fileDecodedContent as $index => $productData) {
            if (!$productData['name'] || !$productData['price']) {
                $output->writeln("Imported Product must contains name and price! - Skipped #{$index} Product");
                continue;
            }
            $productName = $productData['name'];
            // I know it's not stated that the name must be unique - that's just my guess
            if ($repository->findOneBy(['name' => $productName])) {
                $output->writeln("Imported Product with name {$productName} already exists! - Skipped");
                continue;
            }

            $product = new Product();
            $product->setName($productName);
            $product->setPrice($productData['price'] ?? 0);
            $product->setCategory($productData['categoryName']
                ? $categoryRepository->findOneBy(['name' => $productData['categoryName']])
                : null
            );

            $em->persist($product);
            $em->flush();
            $output->writeln("Imported Product with name {$productName} successfully created");
        }

         return Command::SUCCESS;
    }
}
