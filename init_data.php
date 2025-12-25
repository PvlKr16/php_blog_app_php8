<?php

require __DIR__.'/vendor/autoload.php';

use App\Document\Category;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$client = new MongoDB\Client($_ENV['MONGODB_URL']);
$config = new \Doctrine\ODM\MongoDB\Configuration();
$config->setProxyDir(__DIR__.'/var/cache/doctrine/proxies');
$config->setProxyNamespace('Proxies');
$config->setHydratorDir(__DIR__.'/var/cache/doctrine/hydrators');
$config->setHydratorNamespace('Hydrators');
$config->setMetadataDriverImpl(
    \Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver::create(__DIR__.'/src/Document')
);
$config->setDefaultDB($_ENV['MONGODB_DB']);

$dm = DocumentManager::create($client, $config);

// Создаём темы
$categories = ['первая', 'вторая', 'третья'];
foreach ($categories as $name) {
    $category = new Category();
    $category->setName($name);
    $dm->persist($category);
}

echo "Темы созданы!\n";

// Создаём админа (опционально)
echo "Создать администратора? (y/n): ";
$answer = trim(fgets(STDIN));

if ($answer === 'y') {
    echo "Email админа: ";
    $email = trim(fgets(STDIN));

    echo "Имя админа: ";
    $username = trim(fgets(STDIN));

    echo "Пароль админа: ";
    $password = trim(fgets(STDIN));

    $admin = new User();
    $admin->setEmail($email);
    $admin->setUsername($username);
    $admin->setPassword(password_hash($password, PASSWORD_DEFAULT));
    $admin->setIsAdmin(true);

    $dm->persist($admin);
    echo "Администратор создан!\n";
}

$dm->flush();

echo "Готово!\n";