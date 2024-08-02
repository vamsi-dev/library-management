<?php

namespace App\Domain\Book;

use App\Domain\Entity\Constants;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookService
{
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;

    /**
     * @param ValidatorInterface $validator
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $data
     * @return Book
     */
    public function createBook(array $data): Book
    {
        $book = new Book();
        $book->setIsbn($data[Constants::PROPERTY_ISBN]);
        $book->setTitle($data[Constants::PROPERTY_TITLE]);
        $book->setAuthor($data[Constants::PROPERTY_AUTHOR]);

        return $book;
    }

    /**
     * @param Book $book
     * @return void
     */
    public function saveBook(Book $book): void
    {
        $errors = $this->validator->validate($book);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \RuntimeException(implode(', ', $errorMessages));
        }

        $this->entityManager->persist($book);
        $this->entityManager->flush();
    }

    /**
     * @param Book $book
     * @param array $data
     * @return Book
     */
    public function updateBook(Book $book, array $data): Book
    {
        $book->setIsbn($data[Constants::PROPERTY_ISBN]);
        $book->setTitle($data[Constants::PROPERTY_TITLE]);
        $book->setAuthor($data[Constants::PROPERTY_AUTHOR]);

        return $book;
    }

    /**
     * @param Book $book
     * @return void
     */
    public function deleteBook(Book $book): void
    {
        $book->setStatus(BookStatus::DELETED);

        $this->entityManager->remove($book);
        $this->entityManager->flush();
    }
}