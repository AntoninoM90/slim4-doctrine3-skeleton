<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Application\Actions\ActionPayload;

class ListUserActionTest extends UserActionTestCase
{
    public function testActionReturnsFirstPageByDefault()
    {
        $app = $this->getAppWithErrorHandling();

        $this->createUser(['firstName' => 'Anna', 'lastName' => 'Bianchi']);
        $this->createUser(['firstName' => 'Beppe', 'lastName' => 'Verdi']);
        $this->createUser(['firstName' => 'Carlo', 'lastName' => 'Rossi']);

        $request = $this->createRequest('GET', '/users');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::CONTENT_TYPE_JSON, $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(200, $payload['statusCode']);
        $this->assertCount(3, $payload['data']['items']);
        $this->assertSame(3, $payload['data']['pagination']['total']);
        $this->assertSame(1, $payload['data']['pagination']['page']);
        $this->assertSame(10, $payload['data']['pagination']['perPage']);
        $this->assertSame(1, $payload['data']['pagination']['totalPages']);
    }

    public function testActionWithPagination()
    {
        $app = $this->getAppWithErrorHandling();

        $this->createUser(['firstName' => 'Anna', 'lastName' => 'Bianchi']);
        $this->createUser(['firstName' => 'Beppe', 'lastName' => 'Verdi']);
        $this->createUser(['firstName' => 'Carlo', 'lastName' => 'Rossi']);

        $request = $this->createRequest('GET', '/users', [], [], [], 'page=2&perPage=2');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(3, $payload['data']['pagination']['total']);
        $this->assertSame(2, $payload['data']['pagination']['page']);
        $this->assertSame(2, $payload['data']['pagination']['perPage']);
        $this->assertSame(2, $payload['data']['pagination']['totalPages']);
        $this->assertCount(1, $payload['data']['items']);
        $this->assertSame('Carlo', $payload['data']['items'][0]['firstName']);
    }

    public function testActionFallsBackAndClampsInvalidPaginationParameters()
    {
        $app = $this->getAppWithErrorHandling();

        $this->createUser(['firstName' => 'Anna', 'lastName' => 'Bianchi']);
        $this->createUser(['firstName' => 'Beppe', 'lastName' => 'Verdi']);
        $this->createUser(['firstName' => 'Carlo', 'lastName' => 'Rossi']);

        $request = $this->createRequest('GET', '/users', [], [], [], 'page=0&perPage=0');
        $response = $app->handle($request);

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['data']['pagination']['page']);
        $this->assertSame(10, $payload['data']['pagination']['perPage']);
        $this->assertCount(3, $payload['data']['items']);

        $request = $this->createRequest('GET', '/users', [], [], [], 'page=abc&perPage=150');
        $response = $app->handle($request);

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['data']['pagination']['page']);
        $this->assertSame(100, $payload['data']['pagination']['perPage']);
    }

    public function testActionReturnsAllUsers()
    {
        $app = $this->getAppWithErrorHandling();

        $this->createUser(['firstName' => 'Anna', 'lastName' => 'Bianchi']);
        $this->createUser(['firstName' => 'Beppe', 'lastName' => 'Verdi']);

        $request = $this->createRequest('GET', '/users');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertIsArray($payload['data']);
        $this->assertCount(2, $payload['data']['items']);
        $this->assertSame(2, $payload['data']['pagination']['total']);
        $this->assertSame(1, $payload['data']['pagination']['page']);
        $this->assertSame(10, $payload['data']['pagination']['perPage']);
        $this->assertSame(1, $payload['data']['pagination']['totalPages']);
    }
}
