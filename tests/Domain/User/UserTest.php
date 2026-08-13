<?php

declare(strict_types=1);

namespace Tests\Domain\User;

use App\Domain\User\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    private const USERNAME = 'johndoe';
    private const PASSWORD = 'password-hash';
    private const EMAIL = 'john@example.com';
    private const FIRST_NAME = 'John';
    private const LAST_NAME = 'Doe';

    private function createUser(): User
    {
        return new User(
            self::USERNAME,
            self::PASSWORD,
            self::EMAIL,
            self::FIRST_NAME,
            self::LAST_NAME
        );
    }

    public function testConstructorStoresAllValues()
    {
        $user = $this->createUser();

        $this->assertSame(self::USERNAME, $user->getUsername());
        $this->assertSame(self::PASSWORD, $user->getPassword());
        $this->assertSame(self::EMAIL, $user->getEmailAddress());
        $this->assertSame(self::FIRST_NAME, $user->getFirstName());
        $this->assertSame(self::LAST_NAME, $user->getLastName());
        $this->assertNull($user->getId());
    }

    public function testSettersAreFluentAndUpdateValues()
    {
        $user = $this->createUser();

        $this->assertSame($user, $user->setUsername('janedoe'));
        $this->assertSame($user, $user->setPassword('new-password-hash'));
        $this->assertSame($user, $user->setEmailAddress('jane@example.com'));
        $this->assertSame($user, $user->setFirstName('Jane'));
        $this->assertSame($user, $user->setLastName('Smith'));

        $this->assertSame('janedoe', $user->getUsername());
        $this->assertSame('new-password-hash', $user->getPassword());
        $this->assertSame('jane@example.com', $user->getEmailAddress());
        $this->assertSame('Jane', $user->getFirstName());
        $this->assertSame('Smith', $user->getLastName());
    }

    public function testJsonSerializeReturnsAllPublicFields()
    {
        $user = $this->createUser();

        $this->assertSame([
            'id' => null,
            'username' => self::USERNAME,
            'emailAddress' => self::EMAIL,
            'firstName' => self::FIRST_NAME,
            'lastName' => self::LAST_NAME,
        ], $user->jsonSerialize());
    }

    public function testJsonSerializeNeverExposesThePassword()
    {
        $user = $this->createUser();
        $user->setPassword('supersecret');

        $json = json_encode($user, JSON_THROW_ON_ERROR);

        $this->assertArrayNotHasKey('password', $user->jsonSerialize());
        $this->assertIsString($json);
        $this->assertStringNotContainsString('supersecret', $json);
        $this->assertStringNotContainsString('password', $json);
    }
}
