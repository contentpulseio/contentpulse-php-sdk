<?php

declare(strict_types=1);

namespace ContentPulse\Tests\Unit\Http;

use ContentPulse\Core\Exceptions\ApiException;
use ContentPulse\Core\Exceptions\AuthenticationException;
use ContentPulse\Core\Exceptions\NotFoundException;
use ContentPulse\Http\ContentPulseClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContentPulseClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv(ContentPulseClient::BASE_URL_ENV);
    }

    protected function tearDown(): void
    {
        putenv(ContentPulseClient::BASE_URL_ENV);
        parent::tearDown();
    }

    #[Test]
    public function it_defaults_to_contentpulse_io_when_no_base_url_is_provided(): void
    {
        $client = new ContentPulseClient(apiKey: 'test-key');

        $this->assertSame('https://contentpulse.io', $client->getBaseUrl());
    }

    #[Test]
    public function it_resolves_base_url_from_environment_variable(): void
    {
        putenv(ContentPulseClient::BASE_URL_ENV.'=https://staging.contentpulse.io');

        $client = new ContentPulseClient(apiKey: 'test-key');

        $this->assertSame('https://staging.contentpulse.io', $client->getBaseUrl());
    }

    #[Test]
    public function it_strips_trailing_slash_and_api_v1_suffix(): void
    {
        $this->assertSame(
            'http://contentpulse.test:8080',
            ContentPulseClient::resolveBaseUrl('http://contentpulse.test:8080/api/v1/'),
        );

        $this->assertSame(
            'http://contentpulse.test:8080',
            ContentPulseClient::resolveBaseUrl('http://contentpulse.test:8080/'),
        );
    }

    #[Test]
    public function explicit_argument_takes_precedence_over_environment_variable(): void
    {
        putenv(ContentPulseClient::BASE_URL_ENV.'=https://env.example.com');

        $client = new ContentPulseClient(
            apiKey: 'test-key',
            baseUrl: 'https://explicit.example.com',
        );

        $this->assertSame('https://explicit.example.com', $client->getBaseUrl());
    }

    #[Test]
    public function it_returns_decoded_response_body(): void
    {
        $client = $this->makeClientWithResponses([
            new Response(200, [], json_encode(['data' => [['id' => 1, 'slug' => 'hello']]])),
        ]);

        $feed = $client->getContentFeed();

        $this->assertCount(1, $feed->items);
    }

    #[Test]
    public function it_throws_authentication_exception_on_401(): void
    {
        $client = $this->makeClientWithResponses([
            new Response(401, [], json_encode(['message' => 'bad key'])),
        ]);

        $this->expectException(AuthenticationException::class);
        $client->getContentFeed();
    }

    #[Test]
    public function it_throws_not_found_exception_on_404_by_id(): void
    {
        $client = $this->makeClientWithResponses([
            new Response(404, [], json_encode(['message' => 'gone'])),
        ]);

        $this->expectException(NotFoundException::class);
        $client->getContentById('01KRDW4ND6CN9Y7E0S3J0BVBTQ');
    }

    #[Test]
    public function get_content_by_id_rejects_empty_string(): void
    {
        $client = $this->makeClientWithResponses([]);

        $this->expectException(NotFoundException::class);
        $client->getContentById('   ');
    }

    #[Test]
    public function it_does_not_retry_on_connection_failure(): void
    {
        $request = new Request('GET', 'content');
        $client = $this->makeClientWithResponses([
            new ConnectException('boom 1', $request),
            new ConnectException('boom 2', $request),
            new ConnectException('boom 3', $request),
        ]);

        $caught = null;
        try {
            $client->getContentFeed();
        } catch (ApiException $e) {
            $caught = $e;
        }

        $this->assertNotNull($caught, 'Expected an ApiException on connection failure.');
        $this->assertStringContainsString('boom 1', $caught->getMessage());
    }

    /**
     * @param  array<int, Response|\Throwable>  $responses
     */
    private function makeClientWithResponses(array $responses): ContentPulseClient
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $httpClient = new Client([
            'handler' => $stack,
            'base_uri' => 'https://contentpulse.io/api/v1/',
            'headers' => [
                'X-API-Key' => 'test-key',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        return new ContentPulseClient(
            apiKey: 'test-key',
            baseUrl: 'https://contentpulse.io',
            httpClient: $httpClient,
        );
    }
}
