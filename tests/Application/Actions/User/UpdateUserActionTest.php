<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Application\Actions\ActionError;

class UpdateUserActionTest extends UserActionTestCase
{
    private const USER_ROUTE = '/user/';

    public function testUpdateUserReturnsUpdatedUser()
    {
        $app = $this->getAppWithErrorHandling();

        $user = $this->createUser(['firstName' => 'Old', 'lastName' => 'Name']);

        $request = $this->createRequest('PATCH', self::USER_ROUTE . $user->getId())
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'firstName' => 'New',
            'lastName' => 'Surname',
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::CONTENT_TYPE_JSON, $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(200, $payload['statusCode']);
        $this->assertSame($user->getId(), $payload['data']['id']);
        $this->assertSame('New', $payload['data']['firstName']);
        $this->assertSame('Surname', $payload['data']['lastName']);
        $this->assertSame($user->getUsername(), $payload['data']['username']);
        $this->assertSame($user->getEmailAddress(), $payload['data']['emailAddress']);
    }

    public function testUpdateUserWithInvalidBodyReturnsValidationError()
    {
        $app = $this->getAppWithErrorHandling();

        $user = $this->createUser();

        $request = $this->createRequest('PATCH', self::USER_ROUTE . $user->getId())
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode(['username' => 'ab'], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(422, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(422, $payload['statusCode']);
        $this->assertSame(ActionError::VALIDATION_ERROR, $payload['error']['type']);
        $this->assertArrayHasKey('username', $payload['error']['details']);
    }

    public function testUpdateUserAllFieldsArePersisted()
    {
        $app = $this->getAppWithErrorHandling();

        $user = $this->createUser();
        $userId = (int) $user->getId();

        $username = 'update-' . bin2hex(random_bytes(4));
        $emailAddress = $username . '@example.com';

        $request = $this->createRequest('PATCH', self::USER_ROUTE . $userId)
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'username' => $username,
            'emailAddress' => $emailAddress,
            'firstName' => 'All',
            'lastName' => 'Fields',
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame($userId, $payload['data']['id']);
        $this->assertSame($username, $payload['data']['username']);
        $this->assertSame($emailAddress, $payload['data']['emailAddress']);
        $this->assertSame('All', $payload['data']['firstName']);
        $this->assertSame('Fields', $payload['data']['lastName']);

        // Re-read from the database with a fresh repository: the changes must
        // have been flushed.
        $storedUser = $this->getUserRepository()->findUserOfId($userId);

        $this->assertNotNull($storedUser);
        $this->assertSame($username, $storedUser->getUsername());
        $this->assertSame($emailAddress, $storedUser->getEmailAddress());
        $this->assertSame('All', $storedUser->getFirstName());
        $this->assertSame('Fields', $storedUser->getLastName());
    }

    public function testUpdateUserWithDuplicateUsernameReturnsValidationError()
    {
        $app = $this->getAppWithErrorHandling();

        $existingUser = $this->createUser(['username' => 'taken-username']);
        $user = $this->createUser();
        $userId = (int) $user->getId();

        $request = $this->createRequest('PATCH', self::USER_ROUTE . $userId)
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'username' => $existingUser->getUsername(),
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(422, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(422, $payload['statusCode']);
        $this->assertSame(ActionError::VALIDATION_ERROR, $payload['error']['type']);
        $this->assertArrayHasKey('username', $payload['error']['details']);
        $this->assertArrayNotHasKey('emailAddress', $payload['error']['details']);

        // The conflicting update must not have been applied.
        $storedUser = $this->getUserRepository()->findUserOfId($userId);

        $this->assertNotNull($storedUser);
        $this->assertNotSame($existingUser->getUsername(), $storedUser->getUsername());
    }

    public function testUpdateUserWithDuplicateEmailAddressReturnsValidationError()
    {
        $app = $this->getAppWithErrorHandling();

        $existingUser = $this->createUser(['emailAddress' => 'taken@example.com']);
        $user = $this->createUser();
        $userId = (int) $user->getId();

        $request = $this->createRequest('PATCH', self::USER_ROUTE . $userId)
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'emailAddress' => $existingUser->getEmailAddress(),
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

    public function testUpdateUserKeepingOwnUsernameAndEmailAddressIsAccepted()
    {
        $app = $this->getAppWithErrorHandling();

        $user = $this->createUser();
        $userId = (int) $user->getId();

        $request = $this->createRequest('PATCH', self::USER_ROUTE . $userId)
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'username' => $user->getUsername(),
            'emailAddress' => $user->getEmailAddress(),
            'lastName' => 'Kept',
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(200, $payload['statusCode']);
        $this->assertSame($user->getUsername(), $payload['data']['username']);
        $this->assertSame($user->getEmailAddress(), $payload['data']['emailAddress']);
        $this->assertSame('Kept', $payload['data']['lastName']);
    }

    public function testUpdateUserNotFound()
    {
        $app = $this->getAppWithErrorHandling();

        $request = $this->createRequest('PATCH', self::USER_ROUTE . $this->getNonExistentUserId())
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode(['firstName' => 'X'], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(404, $payload['statusCode']);
        $this->assertSame(ActionError::RESOURCE_NOT_FOUND, $payload['error']['type']);
    }
}
