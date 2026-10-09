<?php

namespace App\Command;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Seed\CategoryCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates or reorders the global supermarket aisles from CategoryCatalog.
 * Safe to run multiple times: existing categories are only reordered.
 */
#[AsCommand(name: 'app:seed-categories', description: 'Create / update the global product categories (aisles)')]
class SeedCategoriesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryRepository $categoryRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;

        foreach (CategoryCatalog::CATEGORIES as $index => $name) {
            $category = $this->categoryRepository->findOneBy(['name' => $name]);
            if (!$category) {
                $category = (new Category())->setName($name);
                $this->entityManager->persist($category);
                $created++;
            }
            $category->setSortOrder($index + 1);
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d categories created, %d in total.', $created, count(CategoryCatalog::CATEGORIES)));

        return Command::SUCCESS;
    }
}
