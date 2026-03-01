<?php

declare(strict_types=1);

namespace ContentPulse\Tests\Unit\WordPress;

use ContentPulse\WordPress\Support\ContentPulseEndpointResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContentPulseEndpointResolverTest extends TestCase
{
    #[Test]
    public function it_normalizes_api_base_url_with_api_v1_suffix(): void
    {
        $resolved = ContentPulseEndpointResolver::resolveApiBaseUrl('http://contentpulse.test:8080/api/v1/');

        $this->assertSame('http://contentpulse.test:8080', $resolved);
    }

    #[Test]
    public function it_falls_back_to_defaults_when_environment_values_are_missing(): void
    {
        $apiBaseUrl = ContentPulseEndpointResolver::resolveApiBaseUrlFromEnvironment(
            null,
            'CP_TEST_MISSING_API_CONSTANT',
            'CP_TEST_MISSING_API_ENV'
        );
        $appBaseUrl = ContentPulseEndpointResolver::resolveAppBaseUrlFromEnvironment(
            null,
            'CP_TEST_MISSING_APP_CONSTANT',
            'CP_TEST_MISSING_APP_ENV'
        );

        $this->assertContains($apiBaseUrl, [
            ContentPulseEndpointResolver::DEFAULT_API_BASE_URL,
            ContentPulseEndpointResolver::LOCAL_API_BASE_URL,
        ]);
        $this->assertContains($appBaseUrl, [
            ContentPulseEndpointResolver::DEFAULT_APP_BASE_URL,
            ContentPulseEndpointResolver::LOCAL_APP_BASE_URL,
        ]);
    }

    #[Test]
    public function it_builds_publish_wordpress_endpoint_from_api_base_url(): void
    {
        $endpoint = ContentPulseEndpointResolver::buildPublishWordPressEndpoint(
            'http://contentpulse.test:8080/api/v1/',
            11
        );

        $this->assertSame(
            'http://contentpulse.test:8080/api/v1/content/11/publish-wordpress',
            $endpoint
        );
    }

    #[Test]
    public function it_builds_content_view_url_from_app_base_url(): void
    {
        $url = ContentPulseEndpointResolver::buildContentUrl('', 9);

        $this->assertContains($url, [
            'https://app.contentpulse.io/content/9',
            'http://app.contentpulse.test:5173/content/9',
        ]);
    }
}
