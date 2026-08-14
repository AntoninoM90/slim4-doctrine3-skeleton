<?php

declare(strict_types=1);

namespace Tests\Application\Documentation;

use Tests\TestCase;

class OpenApiSpecTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function getSpec(): array
    {
        $app = $this->getAppWithErrorHandling();

        $response = $app->handle($this->createRequest('GET', '/docs.json'));

        $this->assertEquals(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);

        return $payload;
    }

    public function testHealthOperationDocumentsItsResponses(): void
    {
        $spec = $this->getSpec();

        $operation = $spec['paths']['/health']['get'];

        $this->assertSame([200, 503], array_keys($operation['responses']));

        foreach ($operation['responses'] as $response) {
            $this->assertSame(
                '#/components/schemas/HealthCheck',
                $response['content']['application/json']['schema']['$ref']
            );
        }
    }

    public function testListUsersOperationDocumentsItsResponses(): void
    {
        $spec = $this->getSpec();

        $operation = $spec['paths']['/users']['get'];

        $this->assertSame([200, 500], array_keys($operation['responses']));

        $parameters = $operation['parameters'];

        $this->assertSame(['page', 'perPage'], array_column($parameters, 'name'));
        $this->assertSame(['query', 'query'], array_column($parameters, 'in'));
        $this->assertFalse($parameters[0]['required']);
        $this->assertFalse($parameters[1]['required']);
        $this->assertSame('integer', $parameters[0]['schema']['type']);
        $this->assertSame(1, $parameters[0]['schema']['minimum']);
        $this->assertSame('integer', $parameters[1]['schema']['type']);
        $this->assertSame(1, $parameters[1]['schema']['minimum']);
        $this->assertSame(100, $parameters[1]['schema']['maximum']);

        $okSchema = $operation['responses'][200]['content']['application/json']['schema'];

        $this->assertSame('object', $okSchema['type']);

        $items = $okSchema['properties']['items'];

        $this->assertSame('array', $items['type']);
        $this->assertSame('#/components/schemas/User', $items['items']['$ref']);
        $this->assertSame(
            '#/components/schemas/Pagination',
            $okSchema['properties']['pagination']['$ref']
        );
        $this->assertSame(
            '#/components/responses/InternalServerError',
            $operation['responses'][500]['$ref']
        );
    }

    public function testViewUserOperationDocumentsItsResponses(): void
    {
        $spec = $this->getSpec();

        $operation = $spec['paths']['/user/{id}']['get'];

        $this->assertSame([200, 404, 500], array_keys($operation['responses']));
        $this->assertSame(
            '#/components/schemas/User',
            $operation['responses'][200]['content']['application/json']['schema']['$ref']
        );

        $notFound = $operation['responses'][404]['content']['application/json'];

        $this->assertSame('#/components/schemas/ErrorResponse', $notFound['schema']['$ref']);
        $this->assertSame(404, $notFound['example']['statusCode']);
        $this->assertSame('RESOURCE_NOT_FOUND', $notFound['example']['error']['type']);

        $this->assertSame(
            '#/components/responses/InternalServerError',
            $operation['responses'][500]['$ref']
        );
    }

    public function testCreateUserOperationDocumentsItsResponses(): void
    {
        $spec = $this->getSpec();

        $operation = $spec['paths']['/user']['post'];

        $this->assertSame([201, 409, 422, 500], array_keys($operation['responses']));
        $this->assertSame(
            '#/components/schemas/User',
            $operation['responses'][201]['content']['application/json']['schema']['$ref']
        );

        $conflict = $operation['responses'][409]['content']['application/json'];

        $this->assertSame('#/components/schemas/ErrorResponse', $conflict['schema']['$ref']);
        $this->assertSame(409, $conflict['example']['statusCode']);
        $this->assertSame('RESOURCE_CONFLICT', $conflict['example']['error']['type']);

        $validation = $operation['responses'][422]['content']['application/json'];

        $this->assertSame('#/components/schemas/ErrorResponse', $validation['schema']['$ref']);
        $this->assertSame(422, $validation['example']['statusCode']);
        $this->assertSame('VALIDATION_ERROR', $validation['example']['error']['type']);
        $this->assertArrayHasKey('username', $validation['example']['error']['details']);
        $this->assertArrayHasKey('password', $validation['example']['error']['details']);

        $this->assertSame(
            '#/components/responses/InternalServerError',
            $operation['responses'][500]['$ref']
        );

        $requestSchema = $operation['requestBody']['content']['application/json']['schema'];

        $this->assertTrue($operation['requestBody']['required']);
        $this->assertSame(
            ['username', 'password', 'emailAddress', 'firstName', 'lastName'],
            $requestSchema['required']
        );
    }

    public function testUpdateUserOperationDocumentsItsResponses(): void
    {
        $spec = $this->getSpec();

        $operation = $spec['paths']['/user/{id}']['patch'];

        $this->assertSame([200, 404, 409, 422, 500], array_keys($operation['responses']));
        $this->assertSame(
            '#/components/schemas/User',
            $operation['responses'][200]['content']['application/json']['schema']['$ref']
        );

        $notFound = $operation['responses'][404]['content']['application/json'];

        $this->assertSame('#/components/schemas/ErrorResponse', $notFound['schema']['$ref']);
        $this->assertSame(404, $notFound['example']['statusCode']);
        $this->assertSame('RESOURCE_NOT_FOUND', $notFound['example']['error']['type']);

        $conflict = $operation['responses'][409]['content']['application/json'];

        $this->assertSame('#/components/schemas/ErrorResponse', $conflict['schema']['$ref']);
        $this->assertSame(409, $conflict['example']['statusCode']);
        $this->assertSame('RESOURCE_CONFLICT', $conflict['example']['error']['type']);

        $validation = $operation['responses'][422]['content']['application/json'];

        $this->assertSame('#/components/schemas/ErrorResponse', $validation['schema']['$ref']);
        $this->assertSame(422, $validation['example']['statusCode']);
        $this->assertSame('VALIDATION_ERROR', $validation['example']['error']['type']);
        $this->assertArrayHasKey('username', $validation['example']['error']['details']);

        $this->assertSame(
            '#/components/responses/InternalServerError',
            $operation['responses'][500]['$ref']
        );

        $requestSchema = $operation['requestBody']['content']['application/json']['schema'];

        $this->assertTrue($operation['requestBody']['required']);
        $this->assertSame(
            ['username', 'emailAddress', 'firstName', 'lastName'],
            array_keys($requestSchema['properties'])
        );
        $this->assertArrayNotHasKey('password', $requestSchema['properties']);
    }

    public function testDeleteUserOperationDocumentsItsResponses(): void
    {
        $spec = $this->getSpec();

        $operation = $spec['paths']['/user/{id}']['delete'];

        $this->assertSame([204, 404, 500], array_keys($operation['responses']));

        $this->assertSame('User deleted', $operation['responses'][204]['description']);
        $this->assertArrayNotHasKey('content', $operation['responses'][204]);

        $notFound = $operation['responses'][404]['content']['application/json'];

        $this->assertSame('#/components/schemas/ErrorResponse', $notFound['schema']['$ref']);
        $this->assertSame(404, $notFound['example']['statusCode']);
        $this->assertSame('RESOURCE_NOT_FOUND', $notFound['example']['error']['type']);

        $this->assertSame(
            '#/components/responses/InternalServerError',
            $operation['responses'][500]['$ref']
        );
    }

    public function testDocumentedSchemas(): void
    {
        $spec = $this->getSpec();

        $schemas = $spec['components']['schemas'];

        $this->assertSame(
            ['User', 'Error', 'ErrorResponse', 'HealthCheck', 'Pagination'],
            array_keys($schemas)
        );

        $this->assertSame(
            ['id', 'username', 'emailAddress', 'firstName', 'lastName'],
            array_keys($schemas['User']['properties'])
        );
        $this->assertSame(['type', 'description', 'details'], array_keys($schemas['Error']['properties']));
        $this->assertSame(
            ['statusCode', 'error'],
            array_keys($schemas['ErrorResponse']['properties'])
        );
        $this->assertSame(
            '#/components/schemas/Error',
            $schemas['ErrorResponse']['properties']['error']['$ref']
        );
        $this->assertSame(['status', 'checks'], array_keys($schemas['HealthCheck']['properties']));
        $this->assertSame(
            ['total', 'page', 'perPage', 'totalPages'],
            array_keys($schemas['Pagination']['properties'])
        );
    }

    public function testDocumentedReusableResponses(): void
    {
        $spec = $this->getSpec();

        $responses = $spec['components']['responses'];

        $this->assertSame(['InternalServerError'], array_keys($responses));
        $this->assertSame(
            '#/components/schemas/ErrorResponse',
            $responses['InternalServerError']['content']['application/json']['schema']['$ref']
        );
    }
}
