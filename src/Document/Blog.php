<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[MongoDB\Document(collection: 'blogs')]
class Blog
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $title = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $content = null;

    #[MongoDB\ReferenceOne(storeAs: 'id', targetDocument: Category::class)]
    private ?Category $category = null;

    #[MongoDB\Field(type: 'string')]
    private string $status = 'public'; // 'public' or 'private'

    #[MongoDB\ReferenceMany(targetDocument: User::class, storeAs: 'id')]
    private $participants;

    #[MongoDB\ReferenceOne(storeAs: 'id', targetDocument: User::class)]
    private ?User $author = null;

    #[MongoDB\Field(type: 'date')]
    private \DateTime $createdAt;

    #[MongoDB\Field(type: 'date')]
    private \DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->participants = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getParticipants(): Collection|array
    {
        return $this->participants;
    }

    public function setParticipants($participants): static
    {
        $this->participants = $participants;
        return $this;
    }

    public function addParticipant(User $user): static
    {
        if (!$this->participants->contains($user)) {
            $this->participants->add($user);
        }
        return $this;
    }

    public function removeParticipant(User $user): static
    {
        $this->participants->removeElement($user);
        return $this;
    }

    public function isParticipant(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // Автор всегда участник
        if ($this->author === $user) {
            return true;
        }

        return $this->participants->contains($user);
    }

    public function canView(?User $user): bool
    {
        // Публичные блоги видны всем авторизованным
        if ($this->status === 'public' && $user !== null) {
            return true;
        }

        // Закрытые блоги только для участников
        if ($this->status === 'private') {
            return $this->isParticipant($user);
        }

        return false;
    }

        public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
