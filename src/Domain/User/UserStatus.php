<?php

namespace App\Domain\User;

enum UserStatus: string
{
    case ACTIVE = 'Active';
    case DELETED = 'Deleted';
}