<?php

declare(strict_types=1);

namespace App\Application\Request\User;

use App\Application\Constraint\UniqueUserField;
use App\Application\Request\AbstractRequest;
use App\Domain\User\User;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequest extends AbstractRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 48)]
        #[UniqueUserField]
        public readonly ?string $username = null,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 72)]
        public readonly ?string $password = null,
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        #[UniqueUserField]
        public readonly ?string $emailAddress = null,
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 40)]
        public readonly ?string $firstName = null,
        #[Assert\NotBlank]
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
            password: isset($body['password']) && is_string($body['password']) ? $body['password'] : null,
            emailAddress: isset($body['emailAddress']) && is_string($body['emailAddress'])
                ? $body['emailAddress']
                : null,
            firstName: isset($body['firstName']) && is_string($body['firstName']) ? $body['firstName'] : null,
            lastName: isset($body['lastName']) && is_string($body['lastName']) ? $body['lastName'] : null,
        );
    }

    public function toUser(): User
    {
        assert($this->username !== null);
        assert($this->password !== null);
        assert($this->emailAddress !== null);
        assert($this->firstName !== null);
        assert($this->lastName !== null);

        return new User(
            $this->username,
            password_hash($this->password, PASSWORD_BCRYPT),
            $this->emailAddress,
            $this->firstName,
            $this->lastName
        );
    }
}
