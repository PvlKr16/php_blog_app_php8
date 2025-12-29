<?php

namespace App\Tests;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class WebTestCase extends BaseWebTestCase
{
    protected KernelBrowser $client;
    protected DocumentManager $dm;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get(DocumentManager::class);

        // Очищаем базу данных перед каждым тестом
        $this->clearDatabase();
    }

    protected function clearDatabase(): void
    {
        $collections = [
            'users',
            'blogs',
            'posts',
            'categories',
            'attachments',
        ];

        foreach ($collections as $collection) {
            try {
                $this->dm->getDocumentCollection($collection)->deleteMany([]);
            } catch (\Exception $e) {
                // Игнорируем ошибки, если коллекция не существует
            }
        }

        $this->dm->clear();
    }

    protected function createUser(
        string $username = 'testuser',
        string $email = 'test@example.com',
        string $password = 'password123',
        array $roles = ['ROLE_USER']
    ): User {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles($roles);

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, $password));

        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }

    protected function loginUser(User $user): void
    {
        $this->client->loginUser($user);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->dm)) {
            $this->dm->clear();
        }
    }
}