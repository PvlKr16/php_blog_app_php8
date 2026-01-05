<?php

namespace App\Command;

use App\Document\Department;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:set-default-department',
    description: 'Установить подразделение "Начальники" для всех существующих пользователей',
)]
class SetDefaultDepartmentCommand extends Command
{
    private DocumentManager $dm;

    public function __construct(DocumentManager $dm)
    {
        parent::__construct();
        $this->dm = $dm;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Создаём или находим подразделение "Начальники"
        $department = $this->dm->getRepository(Department::class)->findOneBy(['name' => 'Начальники']);

        if (!$department) {
            $department = new Department();
            $department->setName('Начальники');
            $this->dm->persist($department);
            $this->dm->flush();
            $io->success('Создано подразделение "Начальники"');
        } else {
            $io->info('Подразделение "Начальники" уже существует');
        }

        // Находим всех пользователей без подразделения
        $users = $this->dm->getRepository(User::class)->findAll();
        $updated = 0;

        foreach ($users as $user) {
            if (!$user->getDepartment()) {
                $user->setDepartment($department);
                $this->dm->persist($user);
                $updated++;
            }
        }

        if ($updated > 0) {
            $this->dm->flush();
            $io->success(sprintf('Обновлено пользователей: %d', $updated));
        } else {
            $io->info('Все пользователи уже имеют подразделение');
        }

        return Command::SUCCESS;
    }
}