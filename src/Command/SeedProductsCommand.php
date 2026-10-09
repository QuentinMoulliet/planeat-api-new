<?php

namespace App\Command;

use App\Repository\HouseholdRepository;
use App\Service\ProductCatalogSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Adds the catalogue products missing from a household.
 * Warning: products deleted by the household will be created again.
 */
#[AsCommand(name: 'app:seed-products', description: 'Add the missing default products to a household')]
class SeedProductsCommand extends Command
{
    public function __construct(
        private HouseholdRepository $householdRepository,
        private ProductCatalogSeeder $seeder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('householdId', InputArgument::REQUIRED, 'Household ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $household = $this->householdRepository->find($input->getArgument('householdId'));

        if (!$household) {
            $io->error('Household not found.');

            return Command::FAILURE;
        }

        $io->caution('Products previously deleted by this household will be created again.');
        if ($input->isInteractive() && !$io->confirm('Continue?', true)) {
            return Command::SUCCESS;
        }

        $count = $this->seeder->seed($household);
        $io->success("$count products added to \"{$household->getName()}\".");

        return Command::SUCCESS;
    }
}
