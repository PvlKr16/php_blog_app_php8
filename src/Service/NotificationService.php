<?php

namespace App\Service;

use App\Document\Blog;
use App\Document\BlogView;
use App\Document\Post;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;

class NotificationService
{
    private DocumentManager $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Получить количество непрочитанных уведомлений
     */
    public function getUnreadCount(User $user): int
    {
        $unreadBlogs = $this->getUnreadBlogs($user);
        return count($unreadBlogs);
    }

    /**
     * Получить список непрочитанных блогов с деталями
     */
    public function getUnreadBlogs(User $user): array
    {
        $unreadBlogs = [];

        // Получаем все блоги, где пользователь участник
        $participantBlogs = $this->dm->getRepository(Blog::class)->createQueryBuilder()
            ->field('participants')->references($user)
            ->getQuery()
            ->execute();

        // Получаем блоги, где пользователь автор
        $authorBlogs = $this->dm->getRepository(Blog::class)->findBy(['author' => $user]);

        // Объединяем блоги
        $allBlogs = [];
        foreach ($participantBlogs as $blog) {
            $allBlogs[$blog->getId()] = $blog;
        }
        foreach ($authorBlogs as $blog) {
            $allBlogs[$blog->getId()] = $blog;
        }

        foreach ($allBlogs as $blog) {
            // Получаем информацию о последнем просмотре
            $blogView = $this->dm->getRepository(BlogView::class)->findOneBy([
                'user' => $user,
                'blog' => $blog
            ]);

            $lastViewedAt = $blogView ? $blogView->getLastViewedAt() : null;

            // Если блог никогда не открывали - он непрочитанный
            if (!$lastViewedAt) {
                $unreadBlogs[] = [
                    'blog' => $blog,
                    'reason' => 'new_blog',
                    'newPostsCount' => 0
                ];
                continue;
            }

            // Проверяем, есть ли новые записи с момента последнего просмотра
            // ИСКЛЮЧАЕМ записи, созданные самим пользователем
            $newPostsCount = $this->dm->getRepository(Post::class)->createQueryBuilder()
                ->field('blog')->references($blog)
                ->field('createdAt')->gt($lastViewedAt)
                ->field('author')->notEqual($user->getId())  // ИСКЛЮЧАЕМ свои записи
                ->count()
                ->getQuery()
                ->execute();

            if ($newPostsCount > 0) {
                $unreadBlogs[] = [
                    'blog' => $blog,
                    'reason' => 'new_posts',
                    'newPostsCount' => $newPostsCount
                ];
            }
        }

        return $unreadBlogs;
    }

    /**
     * Отметить блог как прочитанный
     */
    public function markBlogAsRead(User $user, Blog $blog): void
    {
        $blogView = $this->dm->getRepository(BlogView::class)->findOneBy([
            'user' => $user,
            'blog' => $blog
        ]);

        if (!$blogView) {
            $blogView = new BlogView();
            $blogView->setUser($user);
            $blogView->setBlog($blog);
        }

        $blogView->setLastViewedAt(new \DateTime());

        $this->dm->persist($blogView);
        $this->dm->flush();
    }

    /**
     * Уведомить всех участников блога о новой записи
     */
    public function notifyBlogParticipants(Blog $blog, User $excludeUser = null): void
    {
        // Уведомляем автора блога
        if ($blog->getAuthor() && (!$excludeUser || $blog->getAuthor()->getId() !== $excludeUser->getId())) {
            $this->invalidateCache($blog->getAuthor());
        }

        // Уведомляем всех участников
        foreach ($blog->getParticipants() as $participant) {
            if (!$excludeUser || $participant->getId() !== $excludeUser->getId()) {
                $this->invalidateCache($participant);
            }
        }
    }

    /**
     * Инвалидировать кэш для пользователя
     */
    private function invalidateCache(User $user): void
    {
        $userId = $user->getId();
        if (isset($this->cache[$userId])) {
            unset($this->cache[$userId]);
        }
    }

}