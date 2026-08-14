<?php

declare(strict_types=1);

namespace App\Application\Request\User;

use App\Application\Request\AbstractRequest;
use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordRequest extends AbstractRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 72)]
        public readonly ?string $newPassword = null,
    ) {
    }

    /**
     * @param array<array-key, mixed> $body
     */
    public static function fromBody(array $body): static
    {
        return new self(
            newPassword: isset($body['newPassword']) && is_string($body['newPassword'])
                ? $body['newPassword']
                : null,
        );
    }
}
