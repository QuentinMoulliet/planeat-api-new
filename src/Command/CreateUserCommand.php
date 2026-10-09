<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\HouseholdRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Interactively adds a user to an existing household.
 */
#[AsCommand(name: 'app:create-user', description: 'Add a user to an existing household')]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HouseholdRepository $householdRepository,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $households = $this->householdRepository->findAll();
        if (empty($households)) {
            $io->error('No household found. Run app:create-household first.');

            return Command::FAILURE;
        }

        $choices = [];
        foreach ($households as $household) {
            $choices[$household->getId()] = $household->getName();
        }
        $selectedId = array_search($io->choice('Select a household', $choices), $choices);
        $household = $this->householdRepository->find($selectedId);

        $firstName = $io->ask('First name', null, fn(?string $v) => trim((string) $v) ?: throw new \RuntimeException('Required.'));
        $email = $io->ask('Email', null, function (?string $v) {
            $v = strtolower(trim((string) $v));
            if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Invalid email.');
            }
            if ($this->userRepository->findOneBy(['email' => $v])) {
                throw new \RuntimeException('This email is already used.');
            }

            return $v;
        });
        $password = $io->askHidden('Password (8 characters min.)', fn(?string $v) => strlen((string) $v) >= 8 ? $v : throw new \RuntimeException('Too short.'));

        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setRoles(['ROLE_USER'])
            ->setHousehold($household);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success("User $email added to household \"{$household->getName()}\".");

        return Command::SUCCESS;
    }
}
