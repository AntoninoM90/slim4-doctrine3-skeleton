<?php

declare(strict_types=1);

namespace App\Application\Actions;

use JsonSerializable;

class ActionError implements JsonSerializable
{
    public const BAD_REQUEST = 'BAD_REQUEST';
    public const INSUFFICIENT_PRIVILEGES = 'INSUFFICIENT_PRIVILEGES';
    public const NOT_ALLOWED = 'NOT_ALLOWED';
    public const NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const SERVER_ERROR = 'SERVER_ERROR';
    public const UNAUTHENTICATED = 'UNAUTHENTICATED';
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const VERIFICATION_ERROR = 'VERIFICATION_ERROR';

    private string $type;

    private ?string $description;

    /**
     * @var array<string, list<string>>|null
     */
    private ?array $details = null;

    public function __construct(string $type, ?string $description = null)
    {
        $this->type = $type;
        $this->description = $description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description = null): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return array<string, list<string>>|null
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    /**
     * @param array<string, list<string>>|null $details
     */
    public function setDetails(?array $details = null): self
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @return array{type: string, description: string|null, details?: array<string, list<string>>}
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        $serialized = [
            'type' => $this->type,
            'description' => $this->description,
        ];

        if ($this->details !== null) {
            $serialized['details'] = $this->details;
        }

        return $serialized;
    }
}
