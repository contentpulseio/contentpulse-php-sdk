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

use function constant;
use function defined;

class ContentPulseClient implements ContentClientInterface
{
    public const DEFAULT_BASE_URL = 'https://contentpulse.io';

    public const BASE_URL_ENV = 'CONTENTPULSE_BASE_URL';

    private Client $http;

    private LoggerInterface $logger;

    private string $baseUrl;

    public function __construct(
        private readonly string $apiKey,
        ?string $baseUrl = null,
        private readonly int $timeout = 30,
        ?LoggerInterface $logger = null,
        ?Client $httpClient = null,
    ) {
        $this->baseUrl = self::resolveBaseUrl($baseUrl);
        $this->logger = $logger ?? new NullLogger;
        $this->http = $httpClient ?? new Client([
            'base_uri' => $this->baseUrl.'/api/v1/',
            'timeout' => $this->timeout,
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
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

    public function getContentById(string $id): ContentItem
    {
        $id = trim($id);
        if ($id === '') {
            throw new NotFoundException('Content id must be a non-empty ULID string.');
        }

        $response = $this->request('GET', 'content/'.rawurlencode($id));

        $data = $response['data'] ?? $response;

        return ContentItem::fromApiResponse($data);
    }

    /**
     * Retrieve a paginated list of tenant websites.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWebsites(int $page = 1, int $perPage = 50): array
    {
        $response = $this->request('GET', 'websites', [
            'query' => [
                'page' => max(1, $page),
                'per_page' => max(1, $perPage),
            ],
        ]);

        $data = $response['data'] ?? [];
        if (! is_array($data)) {
            return [];
        }

        return array_values(array_filter($data, 'is_array'));
    }

    /**
     * Resolve the API base URL using (in order):
     *   1. The explicit argument
     *   2. The CONTENTPULSE_BASE_URL PHP constant
     *   3. The CONTENTPULSE_BASE_URL environment variable
     *   4. The default https://contentpulse.io
     */
    public static function resolveBaseUrl(?string $baseUrl = null): string
    {
        $candidate = self::normalize((string) ($baseUrl ?? ''));

        if ($candidate === '' && defined(self::BASE_URL_ENV)) {
            $constantValue = constant(self::BASE_URL_ENV);
            if (is_string($constantValue)) {
                $candidate = self::normalize($constantValue);
            }
        }

        if ($candidate === '') {
            $envValue = getenv(self::BASE_URL_ENV);
            if (is_string($envValue)) {
                $candidate = self::normalize($envValue);
            }
        }

        if ($candidate === '') {
            $candidate = self::DEFAULT_BASE_URL;
        }

        if (preg_match('#/api/v1$#i', $candidate) === 1) {
            $candidate = mb_substr($candidate, 0, -7);
        }

        return $candidate;
    }

    private static function normalize(string $baseUrl): string
    {
        return rtrim(trim($baseUrl), '/');
    }

    /**
     * Execute a single HTTP request against the ContentPulse API.
     *
     * No retry/backoff loop is performed here. Network and timeout failures
     * surface immediately as ApiException so callers can decide their own
     * retry policy without the SDK silently blocking on usleep.
     *
     * @param  array<string, mixed>  $options  Guzzle request options
     * @return array<string, mixed>
     */
    private function request(string $method, string $uri, array $options = []): array
    {
        try {
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
            $this->logger->error('ContentPulse API request failed', [
                'method' => $method,
                'uri' => $uri,
                'error' => $e->getMessage(),
            ]);

            throw new ApiException(
                'ContentPulse API request failed: '.$e->getMessage(),
                previous: $e,
            );
        }
    }
}
