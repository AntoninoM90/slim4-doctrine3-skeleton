<?php

declare(strict_types=1);

namespace App\Application\Request\User;

use App\Application\Constraint\UniqueUserField;
use App\Application\Request\AbstractRequest;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateUserRequest extends AbstractRequest
{
    public ?int $excludedUserId = null;

    public function getExcludedUserId(): ?int
    {
        return $this->excludedUserId;
    }

    public function __construct(
        #[Assert\Length(min: 3, max: 48)]
        #[UniqueUserField]
        public readonly ?string $username = null,
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        #[UniqueUserField]
        public readonly ?string $emailAddress = null,
        #[Assert\Length(min: 1, max: 40)]
        public readonly ?string $firstName = null,
        #[Assert\Length(min: 1, max: 40)]
        public readonly ?string $lastName = null,
    ) {
    }

    /**
     * @param array<array-key, mixed> $body
     */
    public static function fromBody(array $body): static
    {
        return new self(
            username: isset($body['username']) && is_string($body['username']) ? $body['username'] : null,
            emailAddress: isset($body['emailAddress']) && is_string($body['emailAddress'])
                ? $body['emailAddress']
                : null,
            firstName: isset($body['firstName']) && is_string($body['firstName']) ? $body['firstName'] : null,
            lastName: isset($body['lastName']) && is_string($body['lastName']) ? $body['lastName'] : null,
        );
    }
}
