<?php

namespace App\Command;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'category:import')]
class CategoryImportCommand extends Command
{
    private $entityManager;

    protected ?string $path = null;
    protected const DEFAULT_PATH = './imports/categories.json';

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
            'Import Categories',
            '============',
            '',
        ]);

        $em = $this->entityManager;
        $repository = $em->getRepository(Category::class);
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

        foreach ($fileDecodedContent as $index => $categoryData) {
            if (!$categoryData['name']) {
                $output->writeln("Imported Category must contains a name! - Skipped #{$index} Category");
                continue;
            }
            // I know it's not stated that the name must be unique - that's just my guess
            $categoryName = $categoryData['name'];
            if ($repository->findOneBy(['name' => $categoryName])) {
                $output->writeln("Imported Category with name {$categoryName} already exists! - Skipped");
                continue;
            }

            $category = new Category();
            $category->setName($categoryName);
            $em->persist($category);
            $em->flush();
            $output->writeln("Imported Category with name {$categoryName} successfully created");
        }

         return Command::SUCCESS;
    }
}
