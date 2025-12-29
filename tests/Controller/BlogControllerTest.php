<?php

namespace App\Tests\Controller;

use App\Document\Blog;
use App\Document\Category;
use App\Tests\WebTestCase;

class BlogControllerTest extends WebTestCase
{
    private function createCategory(string $name = 'Test Category'): Category
    {
        $category = new Category();
        $category->setName($name);

        $this->dm->persist($category);
        $this->dm->flush();

        return $category;
    }

    private function createBlog(
        string $title = 'Test Blog',
        string $content = 'Test Content',
        string $status = 'public'
    ): Blog {
        $user = $this->createUser();
        $category = $this->createCategory();

        $blog = new Blog();
        $blog->setTitle($title);
        $blog->setContent($content);
        $blog->setAuthor($user);
        $blog->setCategory($category);
        $blog->setStatus($status);

        $this->dm->persist($blog);
        $this->dm->flush();

        return $blog;
    }

    public function testBlogListPage(): void
    {
        $this->createBlog('Blog 1');
        $this->createBlog('Blog 2');

        $user = $this->createUser('viewer', 'viewer@example.com');
        $this->loginUser($user);

        $this->client->request('GET', '/blog/');

        $this->assertResponseIsSuccessful();
        // Исправлено: заголовок "Все блоги" вместо "Список блогов"
        $this->assertSelectorTextContains('h1', 'Все блоги');
    }

    public function testCreateBlogRequiresAuthentication(): void
    {
        $this->client->request('GET', '/blog/new');

        $this->assertResponseRedirects('/login');
    }

    public function testAuthenticatedUserCanAccessCreateBlog(): void
    {
        $user = $this->createUser();
        $this->loginUser($user);

        $this->client->request('GET', '/blog/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Создать новый блог');
    }

    public function testCreateBlog(): void
    {
        $user = $this->createUser();
        $this->loginUser($user);
        $category = $this->createCategory();

        $crawler = $this->client->request('GET', '/blog/new');

        $form = $crawler->selectButton('Создать блог')->form([
            'blog[title]' => 'New Test Blog',
            'blog[content]' => 'This is test content for the new blog',
            'blog[category]' => $category->getId(),
            'blog[status]' => 'public',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects();

        // Проверяем, что блог создан
        $blog = $this->dm->getRepository(Blog::class)->findOneBy(['title' => 'New Test Blog']);
        $this->assertNotNull($blog);
        $this->assertEquals('This is test content for the new blog', $blog->getContent());
    }

    public function testViewBlog(): void
    {
        $blog = $this->createBlog('Viewable Blog', 'Viewable Content');

        $user = $this->createUser('viewer', 'viewer@example.com');
        $this->loginUser($user);

        $this->client->request('GET', '/blog/' . $blog->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Viewable Blog');
        $this->assertSelectorTextContains('.card-body', 'Viewable Content');
    }

    public function testEditBlogRequiresOwnership(): void
    {
        $blog = $this->createBlog();

        // Другой пользователь пытается редактировать
        $otherUser = $this->createUser('other', 'other@example.com');
        $this->loginUser($otherUser);

        $this->client->request('GET', '/blog/' . $blog->getId() . '/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testOwnerCanEditBlog(): void
    {
        $user = $this->createUser();
        $blog = $this->createBlog();
        $blog->setAuthor($user);
        $this->dm->flush();

        $this->loginUser($user);

        $this->client->request('GET', '/blog/' . $blog->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Редактировать блог');
    }

    public function testDeleteBlog(): void
    {
        $user = $this->createUser();
        $blog = $this->createBlog();
        $blog->setAuthor($user);
        $this->dm->flush();

        $this->loginUser($user);

        $this->client->request('DELETE', '/blog/' . $blog->getId());

        // Исправлено: ожидаем редирект на /blog/ а не /blog
        $this->assertResponseRedirects('/blog/');

        // Проверяем, что блог удалён
        $deletedBlog = $this->dm->getRepository(Blog::class)->find($blog->getId());
        $this->assertNull($deletedBlog);
    }

    public function testPrivateBlogNotVisibleToNonParticipants(): void
    {
        $owner = $this->createUser('owner', 'owner@example.com');
        $blog = $this->createBlog('Private Blog', 'Private Content', 'private');
        $blog->setAuthor($owner);
        $this->dm->flush();

        // Другой пользователь пытается просмотреть
        $otherUser = $this->createUser('other', 'other@example.com');
        $this->loginUser($otherUser);

        $this->client->request('GET', '/blog/' . $blog->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPrivateBlogVisibleToParticipants(): void
    {
        $owner = $this->createUser('owner', 'owner@example.com');
        $participant = $this->createUser('participant', 'participant@example.com');

        $blog = $this->createBlog('Private Blog', 'Private Content', 'private');
        $blog->setAuthor($owner);
        $blog->addParticipant($participant);
        $this->dm->flush();

        $this->loginUser($participant);

        $this->client->request('GET', '/blog/' . $blog->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Private Blog');
    }
}