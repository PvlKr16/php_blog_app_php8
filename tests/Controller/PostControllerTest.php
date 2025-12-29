<?php

namespace App\Tests\Controller;

use App\Document\Blog;
use App\Document\Category;
use App\Document\Post;
use App\Tests\WebTestCase;

class PostControllerTest extends WebTestCase
{
    private function createBlog(string $status = 'public'): Blog
    {
        $user = $this->createUser();
        $category = new Category();
        $category->setName('Test Category');
        $this->dm->persist($category);

        $blog = new Blog();
        $blog->setTitle('Test Blog');
        $blog->setContent('Test Content');
        $blog->setAuthor($user);
        $blog->setCategory($category);
        $blog->setStatus($status);

        $this->dm->persist($blog);
        $this->dm->flush();

        return $blog;
    }

    public function testCreatePostInPublicBlog(): void
    {
        $blog = $this->createBlog('public');

        $user = $this->createUser('postauthor', 'postauthor@example.com');
        $this->loginUser($user);

        $crawler = $this->client->request('GET', '/post/blog/' . $blog->getId() . '/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Добавить запись')->form([
            'post[title]' => 'New Post',
            'post[content]' => 'This is a new post content',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects();

        $post = $this->dm->getRepository(Post::class)->findOneBy(['title' => 'New Post']);
        $this->assertNotNull($post);
        $this->assertEquals('This is a new post content', $post->getContent());
    }

    public function testViewPost(): void
    {
        $blog = $this->createBlog();
        $user = $this->createUser();

        $post = new Post();
        $post->setTitle('Test Post');
        $post->setContent('Test Post Content');
        $post->setBlog($blog);
        $post->setAuthor($user);

        $this->dm->persist($post);
        $this->dm->flush();

        $this->loginUser($user);

        $this->client->request('GET', '/post/' . $post->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Test Post');
        $this->assertSelectorTextContains('.card-body', 'Test Post Content');
    }

    public function testEditPostRequiresOwnership(): void
    {
        $blog = $this->createBlog();
        $owner = $this->createUser('owner', 'owner@example.com');

        $post = new Post();
        $post->setTitle('Owner Post');
        $post->setContent('Content');
        $post->setBlog($blog);
        $post->setAuthor($owner);

        $this->dm->persist($post);
        $this->dm->flush();

        $otherUser = $this->createUser('other', 'other@example.com');
        $this->loginUser($otherUser);

        $this->client->request('GET', '/post/' . $post->getId() . '/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeletePost(): void
    {
        $blog = $this->createBlog();
        $user = $this->createUser();

        $post = new Post();
        $post->setTitle('Delete Me');
        $post->setContent('Content');
        $post->setBlog($blog);
        $post->setAuthor($user);

        $this->dm->persist($post);
        $this->dm->flush();

        $this->loginUser($user);

        $this->client->request('DELETE', '/post/' . $post->getId());

        $this->assertResponseRedirects();

        $deletedPost = $this->dm->getRepository(Post::class)->find($post->getId());
        $this->assertNull($deletedPost);
    }
}