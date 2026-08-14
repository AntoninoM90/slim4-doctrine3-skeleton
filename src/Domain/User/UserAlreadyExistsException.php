<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\DomainException\DomainConflictException;
use Throwable;

class UserAlreadyExistsException extends DomainConflictException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            'A user with this username or email address already exists.',
            0,
            $previous
        );
    }
}
