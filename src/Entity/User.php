<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\GeneratedValue;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ADMIN_ROLE = 'ROLE_ADMIN';
    public const USER_ROLE = 'ROLE_USER';
    /** To be used later */
    #[ORM\Id]
    #[GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: 'string')]
    #[Assert\PasswordStrength(
        minScore: Assert\PasswordStrength::STRENGTH_VERY_STRONG,
        message: 'Votre mot de passe est trop faible. Veuillez utilisez un mot de passe plus fort.'
    )]
    private string $password;

    /** @var Collection<int, Media> */
    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'user')]
    private Collection $medias;

    /** @var string[] */
    #[ORM\Column(type: 'json', nullable: false, options: ['jsonb' => true])]
    private array $roles = [self::USER_ROLE];

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isGuest = false;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /** @return Collection<int, Media> */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function addRole(string $role): self
    {
        $this->roles[] = $role;

        return $this;
    }

    public function removeRole(string $role): self
    {
        if (false !== $key = array_search($role, $this->roles, true)) {
            unset($this->roles[$key]);
        }

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        if (!isset($this->email) || ('' === $this->email || '0' === $this->email)) {
            throw new \LogicException('User email cannot be empty');
        }

        return $this->email;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setIsGuest(bool $isGuest): User
    {
        $this->isGuest = $isGuest;

        return $this;
    }

    public function isGuest(): bool
    {
        return $this->isGuest;
    }
}
