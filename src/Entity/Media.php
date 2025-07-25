<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\GeneratedValue;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
class Media
{
    #[ORM\Id]
    #[GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'medias')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Album::class)]
    private ?Album $album = null;

    #[ORM\Column]
    private string $path;

    #[ORM\Column]
    private string $title;

    private ?UploadedFile $file = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setFile(?UploadedFile $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): self
    {
        $this->album = $album;

        return $this;
    }
}
