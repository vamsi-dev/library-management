<?php

namespace App\Domain\User;

use App\Domain\Book\Book;
use App\Domain\Borrow\Borrow;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param User $user
     * @param Book $book
     * @return Borrow|null
     */
    public function findActiveBorrowing(User $user, Book $book): ?Borrow
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('b')
            ->from(Borrow::class, 'b')
            ->where('b.user = :user_id')
            ->andWhere('b.book = :book_id')
            ->andWhere('b.checkinDate IS NULL')
            ->setParameter('user_id', $user->getId())
            ->setParameter('book_id', $book->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param User $user
     * @return int
     */
    public function getActiveBorrowingsCount(User $user): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('count(b.id)')
            ->join('u.borrowings', 'b')
            ->where('u.id = :user_id')
            ->andWhere('b.checkinDate IS NULL')
            ->setParameter('user_id', $user->getId())
            ->getQuery();

        return (int) $qb->getSingleScalarResult();
    }
}
