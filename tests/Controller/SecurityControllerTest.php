<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testRegisterPage(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Регистрация');

        // Проверяем наличие полей формы
        $this->assertCount(1, $crawler->filter('input[name="registration_form[username]"]'));
        $this->assertCount(1, $crawler->filter('input[name="registration_form[email]"]'));
    }

    public function testSuccessfulRegistration(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->selectButton('Зарегистрироваться')->form([
            'registration_form[username]' => 'newuser',
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-success');
    }

    public function testLoginPage(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Вход');
    }

    public function testSuccessfulLogin(): void
    {
        // Создаём пользователя
        $user = $this->createUser('testuser', 'test@example.com', 'password123');

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Войти')->form([
            '_username' => 'test@example.com',
            '_password' => 'password123',
        ]);

        $this->client->submit($form);

        // Исправлено: после логина редирект на главную страницу /
        $this->assertResponseRedirects('/');
    }

    public function testFailedLogin(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Войти')->form([
            '_username' => 'wrong@example.com',
            '_password' => 'wrongpassword',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-danger');
    }

    public function testLogout(): void
    {
        $user = $this->createUser();
        $this->loginUser($user);

        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects();
    }
}