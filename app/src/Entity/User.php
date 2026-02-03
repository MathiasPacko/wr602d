<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $lastname = null;

    #[ORM\Column(length: 100)]
    private ?string $firstname = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dob = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $favoriteColor = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\OneToMany(targetEntity: UserContact::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userContacts;

    #[ORM\OneToMany(targetEntity: Generation::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $generations;

    #[ORM\ManyToOne(targetEntity: Plan::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Plan $plan = null;

    public function __construct()
    {
        $this->userContacts = new ArrayCollection();
        $this->generations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getDob(): ?\DateTimeInterface
    {
        return $this->dob;
    }

    public function setDob(?\DateTimeInterface $dob): static
    {
        $this->dob = $dob;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getFavoriteColor(): ?string
    {
        return $this->favoriteColor;
    }

    public function setFavoriteColor(?string $favoriteColor): static
    {
        $this->favoriteColor = $favoriteColor;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getUserContacts(): Collection
    {
        return $this->userContacts;
    }

    public function addUserContact(UserContact $userContact): static
    {
        if (!$this->userContacts->contains($userContact)) {
            $this->userContacts->add($userContact);
            $userContact->setUser($this);
        }
        return $this;
    }

    public function removeUserContact(UserContact $userContact): static
    {
        if ($this->userContacts->removeElement($userContact)) {
            if ($userContact->getUser() === $this) {
                $userContact->setUser(null);
            }
        }
        return $this;
    }

    public function getGenerations(): Collection
    {
        return $this->generations;
    }

    public function addGeneration(Generation $generation): static
    {
        if (!$this->generations->contains($generation)) {
            $this->generations->add($generation);
            $generation->setUser($this);
        }
        return $this;
    }

    public function removeGeneration(Generation $generation): static
    {
        if ($this->generations->removeElement($generation)) {
            if ($generation->getUser() === $this) {
                $generation->setUser(null);
            }
        }
        return $this;
    }

    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    public function setPlan(?Plan $plan): static
    {
        $this->plan = $plan;
        return $this;
    }
}
