<?php

declare(strict_types=1);

namespace ContentPulse\WordPress\Support;

final class ContentPulseEndpointResolver
{
    public const DEFAULT_API_BASE_URL = 'https://api.contentpulse.io';

    public const DEFAULT_APP_BASE_URL = 'https://app.contentpulse.io';

    public const LOCAL_API_BASE_URL = 'http://contentpulse.test:8080';

    public const LOCAL_APP_BASE_URL = 'http://app.contentpulse.test:5173';

    public static function resolveApiBaseUrlFromEnvironment(
        ?string $configuredUrl = null,
        string $constantName = 'CONTENTPULSE_API_URL',
        string $envName = 'CONTENTPULSE_API_URL',
    ): string {
        $resolved = self::resolveConfiguredUrl($configuredUrl, $constantName, $envName);

        return self::resolveApiBaseUrl($resolved);
    }

    public static function resolveAppBaseUrlFromEnvironment(
        ?string $configuredUrl = null,
        string $constantName = 'CONTENTPULSE_APP_URL',
        string $envName = 'CONTENTPULSE_APP_URL',
    ): string {
        $resolved = self::resolveConfiguredUrl($configuredUrl, $constantName, $envName);

        return self::resolveAppBaseUrl($resolved);
    }

    public static function resolveApiBaseUrl(?string $baseUrl = null): string
    {
        $candidate = self::normalizeBaseUrl((string) ($baseUrl ?? ''));

        if ($candidate === '') {
            return self::resolveDefaultApiBaseUrl();
        }

        if (preg_match('#/api/v1$#i', $candidate) === 1) {
            return substr($candidate, 0, -7);
        }

        return $candidate;
    }

    public static function resolveAppBaseUrl(?string $baseUrl = null): string
    {
        $candidate = self::normalizeBaseUrl((string) ($baseUrl ?? ''));

        return $candidate !== '' ? $candidate : self::resolveDefaultAppBaseUrl();
    }

    public static function buildPublishWordPressEndpoint(string $apiBaseUrl, int $contentId): string
    {
        if ($contentId <= 0) {
            return '';
        }

        return self::resolveApiBaseUrl($apiBaseUrl)."/api/v1/content/{$contentId}/publish-wordpress";
    }

    public static function buildContentUrl(string $appBaseUrl, int $contentId): string
    {
        if ($contentId <= 0) {
            return '';
        }

        return self::resolveAppBaseUrl($appBaseUrl)."/content/{$contentId}";
    }

    private static function resolveConfiguredUrl(
        ?string $configuredUrl,
        string $constantName,
        string $envName,
    ): ?string {
        $normalizedConfigured = self::normalizeBaseUrl((string) $configuredUrl);
        if ($normalizedConfigured !== '') {
            return $normalizedConfigured;
        }

        if (\defined($constantName)) {
            $constantValue = \constant($constantName);
            if (is_string($constantValue)) {
                $normalizedConstant = self::normalizeBaseUrl($constantValue);
                if ($normalizedConstant !== '') {
                    return $normalizedConstant;
                }
            }
        }

        $envValue = getenv($envName);
        if (is_string($envValue)) {
            $normalizedEnv = self::normalizeBaseUrl($envValue);
            if ($normalizedEnv !== '') {
                return $normalizedEnv;
            }
        }

        return null;
    }

    private static function normalizeBaseUrl(string $baseUrl): string
    {
        return rtrim(trim($baseUrl), '/');
    }

    private static function resolveDefaultApiBaseUrl(): string
    {
        return self::isHostResolvable('contentpulse.test')
            ? self::LOCAL_API_BASE_URL
            : self::DEFAULT_API_BASE_URL;
    }

    private static function resolveDefaultAppBaseUrl(): string
    {
        return self::isHostResolvable('contentpulse.test')
            ? self::LOCAL_APP_BASE_URL
            : self::DEFAULT_APP_BASE_URL;
    }

    private static function isHostResolvable(string $host): bool
    {
        if ($host === '') {
            return false;
        }

        $resolvedHost = gethostbyname($host);

        return $resolvedHost !== '' && $resolvedHost !== $host;
    }
}
