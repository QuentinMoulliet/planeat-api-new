<?php

namespace App\Command;

use App\Entity\Household;
use App\Service\ProductCatalogSeeder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates a household and fills its product catalogue with the default products.
 */
#[AsCommand(name: 'app:create-household', description: 'Create a new household (with its default product catalogue)')]
class CreateHouseholdCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductCatalogSeeder $seeder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Household name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name') ?? $io->ask('Household name', null, function (?string $value) {
            if (!trim((string) $value)) {
                throw new \RuntimeException('The name cannot be empty.');
            }

            return trim($value);
        });

        $household = (new Household())->setName($name);
        $this->entityManager->persist($household);
        $this->entityManager->flush();

        try {
            $count = $this->seeder->seed($household);
        } catch (\RuntimeException $e) {
            $io->warning("Household created (ID: {$household->getId()}) but products not seeded: {$e->getMessage()}");

            return Command::FAILURE;
        }

        $io->success("Household \"$name\" created (ID: {$household->getId()}) with $count products.");

        return Command::SUCCESS;
    }
}
