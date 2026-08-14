<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Application\Actions\ActionError;

class CreateUserActionTest extends UserActionTestCase
{
    public function testCreateUserReturnsCreatedUser()
    {
        $app = $this->getAppWithErrorHandling();

        $username = 'create-test-' . bin2hex(random_bytes(4));
        $emailAddress = $username . '@example.com';

        $request = $this->createRequest('POST', '/user')
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'username' => $username,
            'password' => 'password123',
            'emailAddress' => $emailAddress,
            'firstName' => 'Create',
            'lastName' => 'Test',
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(self::CONTENT_TYPE_JSON, $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(201, $payload['statusCode']);
        $this->assertSame($username, $payload['data']['username']);
        $this->assertSame($emailAddress, $payload['data']['emailAddress']);
        $this->assertSame('Create', $payload['data']['firstName']);
        $this->assertSame('Test', $payload['data']['lastName']);
        $this->assertArrayNotHasKey('password', $payload['data']);

        $userId = $payload['data']['id'];
        $this->assertIsInt($userId);
        $this->createdUserIds[] = $userId;

        $storedUser = $this->getUserRepository()->findUserOfId($userId);

        $this->assertNotNull($storedUser);
        $this->assertNotSame('password123', $storedUser->getPassword());
        $this->assertTrue(password_verify('password123', $storedUser->getPassword()));
    }

    public function testCreateUserWithDuplicateUsernameReturnsValidationError()
    {
        $app = $this->getAppWithErrorHandling();

        $existingUser = $this->createUser(['username' => 'taken-username']);

        $request = $this->createRequest('POST', '/user')
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'username' => $existingUser->getUsername(),
            'password' => 'password123',
            'emailAddress' => 'other-' . bin2hex(random_bytes(4)) . '@example.com',
            'firstName' => 'Create',
            'lastName' => 'Test',
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(self::CONTENT_TYPE_JSON, $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(422, $payload['statusCode']);
        $this->assertSame(ActionError::VALIDATION_ERROR, $payload['error']['type']);
        $this->assertArrayHasKey('username', $payload['error']['details']);
        $this->assertArrayNotHasKey('emailAddress', $payload['error']['details']);
    }

    public function testCreateUserWithDuplicateEmailAddressReturnsValidationError()
    {
        $app = $this->getAppWithErrorHandling();

        $existingUser = $this->createUser(['emailAddress' => 'taken@example.com']);

        $request = $this->createRequest('POST', '/user')
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'username' => 'other-' . bin2hex(random_bytes(4)),
            'password' => 'password123',
            'emailAddress' => $existingUser->getEmailAddress(),
            'firstName' => 'Create',
            'lastName' => 'Test',
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(422, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(422, $payload['statusCode']);
        $this->assertSame(ActionError::VALIDATION_ERROR, $payload['error']['type']);
        $this->assertArrayHasKey('emailAddress', $payload['error']['details']);
        $this->assertArrayNotHasKey('username', $payload['error']['details']);
    }

    public function testCreateUserWithDuplicateUsernameAndEmailAddressReturnsValidationErrorForBothFields()
    {
        $app = $this->getAppWithErrorHandling();

        $existingUser = $this->createUser([
            'username' => 'taken-both',
            'emailAddress' => 'taken-both@example.com',
        ]);

        $request = $this->createRequest('POST', '/user')
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'username' => $existingUser->getUsername(),
            'password' => 'password123',
            'emailAddress' => $existingUser->getEmailAddress(),
            'firstName' => 'Create',
            'lastName' => 'Test',
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(422, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(422, $payload['statusCode']);
        $this->assertSame(ActionError::VALIDATION_ERROR, $payload['error']['type']);
        $this->assertArrayHasKey('username', $payload['error']['details']);
        $this->assertArrayHasKey('emailAddress', $payload['error']['details']);
    }

    public function testCreateUserWithInvalidBodyReturnsValidationError()
    {
        $app = $this->getAppWithErrorHandling();

        $request = $this->createRequest('POST', '/user')
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'username' => '',
            'password' => 'short',
            'emailAddress' => 'not-an-email',
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(self::CONTENT_TYPE_JSON, $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(422, $payload['statusCode']);
        $this->assertSame(ActionError::VALIDATION_ERROR, $payload['error']['type']);
        $this->assertArrayHasKey('details', $payload['error']);
        $this->assertArrayHasKey('username', $payload['error']['details']);
        $this->assertArrayHasKey('password', $payload['error']['details']);
        $this->assertArrayHasKey('emailAddress', $payload['error']['details']);
        $this->assertArrayHasKey('firstName', $payload['error']['details']);
        $this->assertArrayHasKey('lastName', $payload['error']['details']);

        $this->assertContains(
            'This value is too short. It should have 3 characters or more.',
            $payload['error']['details']['username']
        );
        $this->assertContains(
            'This value is too short. It should have 8 characters or more.',
            $payload['error']['details']['password']
        );
        $this->assertContains(
            'This value is not a valid email address.',
            $payload['error']['details']['emailAddress']
        );
        $this->assertContains(
            'This value should not be blank.',
            $payload['error']['details']['firstName']
        );
        $this->assertContains(
            'This value should not be blank.',
            $payload['error']['details']['lastName']
        );
    }
}
