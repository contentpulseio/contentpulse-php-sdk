<?php

declare(strict_types=1);

namespace ContentPulse\Media;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Downloads remote ContentPulse images to a local directory and returns
 * a public URL that the host application can serve.
 *
 * Framework-agnostic: uses plain PHP filesystem + Guzzle HTTP.
 * Laravel users should prefer the framework-specific ImageDownloader
 * in the contentpulseio/laravel package which uses Storage disks.
 */
class ImageDownloader
{
    private Client $http;

    private LoggerInterface $logger;

    /**
     * @param  string  $storagePath  Absolute path to the directory where images are stored on disk.
     * @param  string  $publicUrlPrefix  URL prefix prepended to the filename to produce a public URL
     *                                   (e.g. "/storage/media/blog" or "https://cdn.example.com/blog").
     * @param  bool  $enabled  When false, localize() returns the original URL untouched.
     * @param  int  $timeout  HTTP download timeout in seconds.
     * @param  string|null  $sourceBaseUrl  ContentPulse host used for storage-relative image paths.
     */
    public function __construct(
        private readonly string $storagePath,
        private readonly string $publicUrlPrefix = '/storage/media/blog',
        private readonly bool $enabled = true,
        private readonly int $timeout = 30,
        ?LoggerInterface $logger = null,
        ?Client $httpClient = null,
        private readonly ?string $sourceBaseUrl = null,
    ) {
        $this->logger = $logger ?? new NullLogger;
        $this->http = $httpClient ?? new Client(['timeout' => $this->timeout]);
    }

    /**
     * Download a remote image locally (if not already present) and return
     * the public URL. On failure, returns the original URL so rendering
     * never breaks.
     */
    public function localize(?string $url): ?string
    {
        if ($url === null || $url === '' || ! $this->enabled) {
            return $url;
        }

        $url = $this->absoluteContentPulseStorageUrl($url);

        if (! $this->isAbsoluteHttpUrl($url)) {
            return $url;
        }

        $filename = $this->targetFilename($url);
        $localPath = rtrim($this->storagePath, '/').'/'.$filename;

        if (! file_exists($localPath)) {
            try {
                $this->ensureDirectory($this->storagePath);

                $response = $this->http->get($url);

                if ($response->getStatusCode() !== 200) {
                    $this->logger->warning('ContentPulse: image download failed', [
                        'url' => $url,
                        'status' => $response->getStatusCode(),
                    ]);

                    return $url;
                }

                file_put_contents($localPath, $response->getBody()->getContents());
            } catch (GuzzleException $e) {
                $this->logger->warning('ContentPulse: image download threw', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);

                return $url;
            }
        }

        return rtrim($this->publicUrlPrefix, '/').'/'.$filename;
    }

    /**
     * Rewrite all image URLs in a variant map.
     *
     * Handles both flat string values and nested {"url": "..."} structures.
     *
     * @param  array<string, mixed>  $images
     * @return array<string, mixed>
     */
    public function localizeImageMap(array $images): array
    {
        foreach ($images as $key => $value) {
            if (is_string($value)) {
                $images[$key] = $this->localize($value);
            } elseif (is_array($value) && isset($value['url']) && is_string($value['url'])) {
                $value['url'] = $this->localize($value['url']);
                $images[$key] = $value;
            }
        }

        return $images;
    }

    /**
     * Content-addressed filename: SHA-1 of the URL (query params stripped)
     * with original extension preserved.
     */
    private function targetFilename(string $url): string
    {
        $withoutQuery = strtok($url, '?') ?: $url;
        $ext = pathinfo((string) parse_url($withoutQuery, PHP_URL_PATH), PATHINFO_EXTENSION);
        $ext = $ext !== '' ? mb_strtolower($ext) : 'jpg';

        return sha1($withoutQuery).'.'.$ext;
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function isAbsoluteHttpUrl(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    /**
     * Chart sections use public storage paths (/storage/content/...) while
     * translated images can use tenants/... or content/... directly. Resolve
     * those upstream-only paths before storing them on the consumer's server.
     */
    private function absoluteContentPulseStorageUrl(string $url): string
    {
        if ($this->isAbsoluteHttpUrl($url)) {
            return $url;
        }

        $path = ltrim($url, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if ((! str_starts_with($path, 'content/') && ! str_starts_with($path, 'tenants/')) || $this->sourceBaseUrl === null || $this->sourceBaseUrl === '') {
            return $url;
        }

        return rtrim($this->sourceBaseUrl, '/').'/storage/'.$path;
    }
}
