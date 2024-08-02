<?php

namespace App\Domain\Borrow;

use App\Domain\Book\Book;
use App\Domain\User\User;
use App\Domain\Entity\Timestamp;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BorrowRepository::class)]
#[ORM\Table(name: "borrowings")]
#[ORM\Index(name: "user_book_idx", columns: ["user_id", "book_id"])]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Borrow
{
    use Timestamp;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['borrow', 'user', 'book'])]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'borrowings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['borrow', 'user'])]
    private Book $book;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'borrowings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['borrow', 'book'])]
    private User $user;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['borrow'])]
    #[Assert\NotNull]
    private DateTimeInterface $checkoutDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['borrow'])]
    private DateTimeInterface $checkinDate;

    /**
     * @param User $user
     * @param Book $book
     */
    public function __construct(User $user, Book $book)
    {
        $this->user = $user;
        $this->book = $book;
        $this->checkoutDate = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(Book $book): self
    {
        $this->book = $book;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCheckoutDate(): ?DateTimeInterface
    {
        return $this->checkoutDate;
    }

    public function setCheckoutDate(DateTimeInterface $checkoutDate): self
    {
        $this->checkoutDate = $checkoutDate;

        return $this;
    }

    public function getCheckinDate(): ?DateTimeInterface
    {
        return $this->checkinDate;
    }

    public function setCheckinDate(?DateTimeInterface $checkinDate): self
    {
        $this->checkinDate = $checkinDate;

        return $this;
    }

    public function return(): void
    {
        if (!empty($this->checkinDate)) {
            throw new \RuntimeException("Book has been already returned");
        }

        $this->checkinDate = new DateTime();
        $this->book->markBookAvailable();
    }
}