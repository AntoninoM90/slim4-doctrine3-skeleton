<?php

declare(strict_types=1);

namespace App\Domain\User;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use JsonSerializable;

#[
    Entity(),
    Table(name: "user")
]
class User implements JsonSerializable
{
    #[Id, Column(
        name: 'id',
        type: 'integer',
        length: 18,
        unique: true,
        nullable: false
    ), GeneratedValue('IDENTITY')]
    private ?int $id;

    #[Column(
        name: 'username',
        type: 'string',
        length: 48,
        unique: true,
        nullable: false
    )]
    private string $username;

    #[Column(
        name: 'first_name',
        type: 'string',
        length: 40,
        unique: false,
        nullable: false
    )]
    private string $firstName;

    #[Column(
        name: 'last_name',
        type: 'string',
        length: 40,
        unique: false,
        nullable: false
    )]
    private string $lastName;

    public function __construct(
        ?int $id,
        string $username,
        string $firstName,
        string $lastName
    ) {
        $this->id = $id;
        $this->username = strtolower($username);
        $this->firstName = ucfirst($firstName);
        $this->lastName = ucfirst($lastName);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUsername(
        string $username
    ): self {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setFirstName(
        string $firstName
    ): self {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setLastName(
        string $lastName
    ): self {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
        ];
    }
}
