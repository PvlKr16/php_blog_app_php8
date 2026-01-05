<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'departments')]
class Department
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[MongoDB\Index(unique: true)]
    private ?string $name = null;

    #[MongoDB\Field(type: 'date')]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
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
}