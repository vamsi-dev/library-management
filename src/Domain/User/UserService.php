<?php

namespace App\Domain\User;

use App\Domain\Borrow\Borrow;
use App\Domain\Entity\Constants;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Email;
use App\Domain\Book\BookRepository;
use App\Domain\ValueObject\Password;
use App\Exception\BookNotFoundException;
use App\Exception\UserNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{
    private ValidatorInterface $validator;
    private UserRepository $userRepository;
    private BookRepository $bookRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ValidatorInterface $validator,
        BookRepository $bookRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager

    ) {
        $this->validator = $validator;
        $this->bookRepository = $bookRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $name
     * @param string $email
     * @param string $password
     * @return User
     */
    public function createUser(string $name, string $email, string $password): User
    {
        return new User(new Name($name), new Email($email), new Password($password));
    }

    /**
     * @param User $user
     * @param string $name
     * @param string $email
     * @param string|null $password
     * @return User
     */
    public function updateUser(User $user, string $name, string $email, ?string $password = null): User
    {
        $user->setName(new Name($name));
        $user->setEmail(new Email($email));
        if ($password !== null) {
            $user->setPassword(new Password($password));
        }

        return $user;
    }

    /**
     * @param User $user
     * @return void
     * @throws Exception
     */
    public function saveUser(User $user): void
    {
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new Exception(implode(', ', $errorMessages));
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @param User $user
     * @return void
     */
    public function deleteUser(User $user): void
    {
        $user->setStatus(UserStatus::DELETED);

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    /**
     * @param int $userId
     * @param int $bookId
     * @return void
     * @throws BookNotFoundException
     * @throws UserNotFoundException
     */
    public function borrowBook(int $userId, int $bookId): void
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException();
        }

        $book = $this->bookRepository->find($bookId);
        if (!$book) {
            throw new BookNotFoundException();
        }

        if ($this->userRepository->getActiveBorrowingsCount($user) >= 5) {
            throw new RuntimeException(Constants::MSG_MAXIMUM_BOOKS_BORROWED);
        }

        $borrowing = new Borrow($user, $book);
        $book->markBookBorrowed();

        $this->entityManager->persist($borrowing);
        $this->entityManager->flush();
    }

    /**
     * @param int $userId
     * @param int $bookId
     * @return void
     * @throws BookNotFoundException
     * @throws UserNotFoundException
     */
    public function returnBook(int $userId, int $bookId): void
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException();
        }

        $book = $this->bookRepository->find($bookId);
        if (!$book) {
            throw new BookNotFoundException();
        }

        $borrowing = $this->userRepository->findActiveBorrowing($user, $book);
        if (!$borrowing) {
            throw new RuntimeException(Constants::MSG_NO_BORROW_OR_RETURNED);
        }

        $borrowing->return();

        $this->entityManager->flush();
    }
}