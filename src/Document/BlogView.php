<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'blog_views')]
#[MongoDB\Index(keys: ['user' => 'asc', 'blog' => 'asc'], options: ['unique' => true])]
class BlogView
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\ReferenceOne(targetDocument: User::class, storeAs: 'id')]
    private ?User $user = null;

    #[MongoDB\ReferenceOne(targetDocument: Blog::class, storeAs: 'id')]
    private ?Blog $blog = null;

    #[MongoDB\Field(type: 'date')]
    private \DateTime $lastViewedAt;

    public function __construct()
    {
        $this->lastViewedAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getBlog(): ?Blog
    {
        return $this->blog;
    }

    public function setBlog(?Blog $blog): static
    {
        $this->blog = $blog;
        return $this;
    }

    public function getLastViewedAt(): \DateTime
    {
        return $this->lastViewedAt;
    }

    public function setLastViewedAt(\DateTime $lastViewedAt): static
    {
        $this->lastViewedAt = $lastViewedAt;
        return $this;
    }
}