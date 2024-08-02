<?php

namespace App\Tests\Domain\Borrow;

use App\Domain\Book\Book;
use App\Domain\Borrow\Borrow;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BorrowingTest extends KernelTestCase
{
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Borrow::class);
    }

    public function testCreateBorrowing()
    {
        $bookRrepository = $this->entityManager->getRepository(Book::class);
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email.email' => 'user6@example.com']);
        $book = $bookRrepository->findOneBy(['title' => 'Book Title 4']);

        $borrowing = new Borrow($user, $book);
        $this->entityManager->persist($borrowing);
        $this->entityManager->flush();

        $savedBorrowing = $this->repository->find($borrowing->getId());

        $this->assertInstanceOf(Borrow::class, $savedBorrowing);
        $this->assertEquals($user->getId(), $savedBorrowing->getUser()->getId());
        $this->assertEquals($book->getId(), $savedBorrowing->getBook()->getId());
    }

    public function testReadBorrowing()
    {
        $borrowing = $this->repository->findOneBy(['checkoutDate' => new \DateTime('now')]);

        $this->assertInstanceOf(Borrow::class, $borrowing);
        $this->assertNotNull($borrowing->getUser());
        $this->assertNotNull($borrowing->getBook());
    }

    public function testUpdateBorrowing()
    {
        $borrowing = $this->repository->findOneBy(['checkoutDate' => new \DateTime('now')]);

        $newCheckinDate = new \DateTime('now');
        $borrowing->setCheckinDate($newCheckinDate);
        $this->entityManager->flush();

        $updatedBorrowing = $this->repository->find($borrowing->getId());
        $this->assertEquals($newCheckinDate, $updatedBorrowing->getCheckinDate());
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
