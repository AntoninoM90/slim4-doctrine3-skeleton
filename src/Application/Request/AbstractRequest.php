<?php

declare(strict_types=1);

namespace App\Application\Request;

abstract class AbstractRequest
{
    /**
     * @param array<array-key, mixed> $body
     */
    abstract public static function fromBody(array $body): static;
}
