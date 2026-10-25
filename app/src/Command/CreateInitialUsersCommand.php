<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:users:create-initial',
    description: 'Crea los usuarios iniciales del restaurante.',
)]
final class CreateInitialUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'password',
            null,
            InputOption::VALUE_REQUIRED,
            'Contraseña inicial para los usuarios nuevos.',
            '1234',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $password = (string) $input->getOption('password');
        $users = [
            'supervisor' => ['ROLE_USER'],
            'mesero' => ['ROLE_USER'],
            'cocinero' => ['ROLE_USER'],
            'cajero' => ['ROLE_USER'],
        ];

        foreach ($users as $username => $roles) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
            $created = false;

            if (!$user instanceof User) {
                $user = (new User())->setUsername($username)->setRoles($roles)->setActive(true);
                $this->entityManager->persist($user);
                $created = true;
            }

            if ($created) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));
                $output->writeln(sprintf('<info>Creado:</info> %s', $username));
            } else {
                $output->writeln(sprintf('<comment>Ya existe:</comment> %s', $username));
            }
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
