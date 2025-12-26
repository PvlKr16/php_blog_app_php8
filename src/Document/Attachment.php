<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'attachments')]
class Attachment
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $filename = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $originalFilename = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $mimeType = null;

    #[MongoDB\Field(type: 'int')]
    private ?int $fileSize = null;

    #[MongoDB\ReferenceOne(targetDocument: Blog::class, storeAs: 'id')]
    private ?Blog $blog = null;

    #[MongoDB\Field(type: 'date')]
    private \DateTime $uploadedAt;

    public function __construct()
    {
        $this->uploadedAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;
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

    public function getUploadedAt(): \DateTime
    {
        return $this->uploadedAt;
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->fileSize;
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }

    public function getFileType(): string
    {
        if (str_starts_with($this->mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($this->mimeType, 'audio/')) {
            return 'audio';
        } elseif (in_array($this->mimeType, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'text/markdown'])) {
            return 'document';
        }
        return 'other';
    }

    public function getIcon(): string
    {
        return match($this->getFileType()) {
            'image' => '🖼️',
            'audio' => '🎵',
            'document' => '📄',
            default => '📎',
        };
    }
}