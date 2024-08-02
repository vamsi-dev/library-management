<?php

namespace App\Domain\Book;

enum BookStatus: string
{
    case DELETED = 'Deleted';
    case BORROWED = 'Borrowed';
    case AVAILABLE = 'Available';
}