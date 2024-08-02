<?php

namespace App\Tests\Domain\User;

use App\Domain\Book\BookRepository;
use App\Domain\Borrow\Borrow;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use App\Domain\User\UserService;
use App\Exception\BookNotFoundException;
use App\Exception\UserNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private BookRepository $bookRepository;
    private ValidatorInterface $validator;
    private UserService $userService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->bookRepository = $container->get(BookRepository::class);
        $this->validator = $container->get(ValidatorInterface::class);
        $this->userService = new UserService($this->userRepository, $this->bookRepository, $this->entityManager, $this->validator);
    }

    public function testCreateUser(): void
    {
        $user = $this->userService->createUser('John Doe', 'john.doe.you@example.com', 'password123');
        $this->userService->saveUser($user);

        $savedUser = $this->userRepository->findOneBy(['email.email' => 'john.doe.you@example.com']);

        $this->assertInstanceOf(User::class, $savedUser);
        $this->assertEquals('John Doe', $savedUser->getName());
        $this->assertEquals('john.doe.you@example.com', $savedUser->getEmail());
    }

    public function testUpdateUser(): void
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'john.doe.you@example.com']);

        $updatUser = $this->userService->updateUser($user, 'Jane Doe', 'john.doe.you@example.com', 'newpassword123');
        $this->userService->saveUser($updatUser);

        $updatedUser = $this->userRepository->findOneBy(['email.email' => 'john.doe.you@example.com']);
        $this->assertEquals('Jane Doe', $updatedUser->getName());
    }

    public function testDeleteUser(): void
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'john.doe.you@example.com']);

        $this->userService->deleteUser($user);

        $deletedUser = $this->userRepository->findOneBy(['email.email' => 'john.doe.you@example.com']);
        $this->assertNull($deletedUser);
    }

    public function testBorrowBook(): void
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'user3@example.com']);
        $book = $this->bookRepository->findOneBy(['title' => 'Book Title 3']);

        $this->userService->borrowBook($user->getId(), $book->getId());

        $borrowing = $this->entityManager->getRepository(Borrow::class)->findOneBy(['user' => $user, 'book' => $book]);
        $this->assertInstanceOf(Borrow::class, $borrowing);
    }

    public function testReturnBook()
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'user3@example.com']);
        $book = $this->bookRepository->findOneBy(['title' => 'Book Title 3']);

        $this->userService->returnBook($user->getId(), $book->getId());

        $borrowing = $this->entityManager->getRepository(Borrow::class)->findOneBy(['user' => $user, 'book' => $book]);
        $this->assertNotNull($borrowing->getCheckinDate());
    }

    public function testBorrowBookUserNotFound()
    {
        $book = $this->bookRepository->findOneBy(['title' => 'Book Title 4']);

        $this->expectException(UserNotFoundException::class);
        $this->userService->borrowBook(999, $book->getId());
    }

    public function testBorrowBookBookNotFound()
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'user4@example.com']);

        $this->expectException(BookNotFoundException::class);
        $this->userService->borrowBook($user->getId(), 999);
    }

    public function testReturnBookUserNotFound()
    {
        $book = $this->bookRepository->findOneBy(['title' => 'Book Title 4']);

        $this->expectException(UserNotFoundException::class);
        $this->userService->returnBook(999, 1);
    }

    public function testReturnBookBookNotFound()
    {
        $user = $this->userRepository->findOneBy(['email.email' => 'user4@example.com']);

        $this->expectException(BookNotFoundException::class);
        $this->userService->returnBook($user->getId(), 999);
    }

    protected function restoreExceptionHandler(): void
    {
        while (true) {
            $previousHandler = set_exception_handler(static fn() => null);
            restore_exception_handler();

            if ($previousHandler === null) {
                break;
            }

            restore_exception_handler();
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->restoreExceptionHandler();
    }
}