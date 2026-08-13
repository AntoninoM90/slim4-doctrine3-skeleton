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

        $okSchema = $operation['responses'][200]['content']['application/json']['schema'];

        $this->assertSame('array', $okSchema['type']);
        $this->assertSame('#/components/schemas/User', $okSchema['items']['$ref']);
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

    public function testDocumentedSchemas(): void
    {
        $spec = $this->getSpec();

        $schemas = $spec['components']['schemas'];

        $this->assertSame(['User', 'Error', 'ErrorResponse', 'HealthCheck'], array_keys($schemas));

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
