<?php

declare(strict_types=1);

namespace ContentPulse\Http;

use ContentPulse\Core\Contracts\ContentClientInterface;
use ContentPulse\Core\DTO\ContentFeed;
use ContentPulse\Core\DTO\ContentFilters;
use ContentPulse\Core\DTO\ContentItem;
use ContentPulse\Core\Exceptions\ApiException;
use ContentPulse\Core\Exceptions\AuthenticationException;
use ContentPulse\Core\Exceptions\NotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ContentPulseClient implements ContentClientInterface
{
    private Client $http;

    private LoggerInterface $logger;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 30,
        private readonly int $retries = 2,
        ?LoggerInterface $logger = null,
        ?Client $httpClient = null,
    ) {
        $this->logger = $logger ?? new NullLogger;
        $this->http = $httpClient ?? new Client([
            'base_uri' => rtrim($this->baseUrl, '/').'/api/v1/',
            'timeout' => $this->timeout,
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function getContentFeed(?ContentFilters $filters = null): ContentFeed
    {
        $params = $filters?->toQueryParams() ?? [];
        $response = $this->request('GET', 'content', ['query' => $params]);

        return ContentFeed::fromApiResponse($response);
    }

    public function getContentBySlug(string $slug): ContentItem
    {
        $response = $this->request('GET', 'content', [
            'query' => ['slug' => $slug, 'per_page' => 1],
        ]);

        $items = $response['data'] ?? [];
        if (empty($items)) {
            throw new NotFoundException("Content with slug '{$slug}' not found.");
        }

        return ContentItem::fromApiResponse($items[0]);
    }

    public function getContentById(int $id): ContentItem
    {
        $response = $this->request('GET', "content/{$id}");

        $data = $response['data'] ?? $response;

        return ContentItem::fromApiResponse($data);
    }

    /**
     * @param  array<string, mixed>  $options  Guzzle request options
     * @return array<string, mixed>
     */
    private function request(string $method, string $uri, array $options = []): array
    {
        $attempt = 0;

        while (true) {
            try {
                $attempt++;
                $response = $this->http->request($method, $uri, $options);
                $body = json_decode((string) $response->getBody(), true);

                return is_array($body) ? $body : [];
            } catch (ClientException $e) {
                $status = $e->getResponse()->getStatusCode();
                $responseBody = json_decode((string) $e->getResponse()->getBody(), true) ?: [];

                if ($status === 401) {
                    throw new AuthenticationException(
                        $responseBody['message'] ?? 'Authentication failed.',
                        $e,
                    );
                }

                if ($status === 404) {
                    throw new NotFoundException(
                        $responseBody['message'] ?? 'Resource not found.',
                        $e,
                    );
                }

                throw new ApiException(
                    $responseBody['message'] ?? "API request failed with status {$status}.",
                    $status,
                    $responseBody,
                    $e,
                );
            } catch (GuzzleException $e) {
                if ($attempt > $this->retries) {
                    $this->logger->error('ContentPulse API request failed after retries', [
                        'method' => $method,
                        'uri' => $uri,
                        'attempts' => $attempt,
                        'error' => $e->getMessage(),
                    ]);

                    throw new ApiException(
                        'ContentPulse API request failed: '.$e->getMessage(),
                        previous: $e,
                    );
                }

                $this->logger->warning('ContentPulse API request failed, retrying', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                usleep(500_000 * $attempt);
            }
        }
    }
}
