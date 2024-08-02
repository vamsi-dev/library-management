<?php

namespace App\Domain\User;

use App\Domain\Borrow\Borrow;
use Doctrine\ORM\Mapping as ORM;
use App\Domain\Entity\Timestamp;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Password;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use Timestamp;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user', 'borrow', 'book'])]
    private ?int $id;

    #[ORM\Embedded(class: Name::class, columnPrefix: false)]
    #[Groups(['user', 'borrow', 'book'])]
    private Name $name;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
    #[Groups(['user'])]
    private Email $email;

    #[ORM\Embedded(class: Password::class, columnPrefix: false)]
    private Password $password;

    #[ORM\Column(type: 'json')]
    #[Groups(['user'])]
    private array $roles;

    #[ORM\Column(type: 'string', enumType: UserStatus::class)]
    #[Groups(['user'])]
    private UserStatus $status;

    #[ORM\OneToMany(targetEntity: Borrow::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user'])]
    private $borrow;

    /**
     * @param Name $name
     * @param Email $email
     * @param Password $password
     */
    public function __construct(Name $name, Email $email, Password $password)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->roles = ['ROLE_USER'];
        $this->status = UserStatus::ACTIVE;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->name;
    }

    /**
     * @param Name $name
     * @return $this
     */
    public function setName(Name $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return (string) $this->email;
    }

    /**
     * @param Email $email
     * @return $this
     */
    public function setEmail(Email $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    /**
     * @param Password $password
     * @return $this
     */
    public function setPassword(Password $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return UserStatus
     */
    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    /**
     * @param UserStatus $status
     * @return $this
     */
    public function setStatus(UserStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @return Collection
     */
    public function getBorrow(): Collection
    {
        return $this->borrow;
    }

    /**
     * @return void
     */
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }
}