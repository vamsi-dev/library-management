<?php

namespace App\Domain\Book;

use App\Domain\Borrow\Borrow;
use App\Domain\Entity\Timestamp;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: "books")]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Book
{
    use Timestamp;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['book', 'borrow', 'user'])]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 150)]
    #[Assert\NotBlank(message: "Title cannot not be blank")]
    #[Assert\Length(max: 150, maxMessage: 'Title cannot be longer than {{ limit }}')]
    #[Groups(['book', 'borrow', 'user'])]
    private ?string $title;

    #[ORM\Column(type: 'string', length: 150)]
    #[Assert\NotBlank(message: "Author should cannot be blank")]
    #[Assert\Length(max: 150, maxMessage: 'Author cannot be longer than {{ limit }}')]
    #[Groups(['book', 'borrow', 'user'])]
    private ?string $author;

    #[ORM\Column(type: 'string', length: 13)]
    #[Assert\NotBlank(message: "ISBN cannot not be blank")]
    #[Assert\Isbn(
        type: Assert\Isbn::ISBN_10,
        message: 'Invalid ISBN',
    )]
    #[Groups(['book'])]
    private ?string $isbn;

    #[ORM\Column(type: 'string', enumType: BookStatus::class)]
    #[Groups(['book'])]
    private BookStatus $status;

    #[ORM\OneToMany(targetEntity: Borrow::class, mappedBy: 'book', cascade: ['persist', 'remove'])]
    #[Groups(['book'])]
    private ArrayCollection $borrowings;

    public function __construct()
    {
        $this->borrowings = new ArrayCollection();
        $this->status = BookStatus::AVAILABLE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): self
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getStatus(): BookStatus
    {
        return $this->status;
    }

    public function setStatus(BookStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getBorrowings(): Collection
    {
        return $this->borrowings;
    }

    /**
     * @throws Exception
     */
    public function markBookBorrowed(): void
    {
        if ($this->status !== BookStatus::AVAILABLE) {
            throw new \RuntimeException("Book is not available");
        }

        $this->status = BookStatus::BORROWED;
    }

    public function markBookAvailable(): void
    {
        $this->status = BookStatus::AVAILABLE;
    }
}