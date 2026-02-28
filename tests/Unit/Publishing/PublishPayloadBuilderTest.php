<?php

declare(strict_types=1);

namespace ContentPulse\Tests\Unit\Publishing;

use ContentPulse\Core\DTO\ContentItem;
use ContentPulse\Publishing\PublishPayloadBuilder;
use ContentPulse\Rendering\HtmlRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PublishPayloadBuilderTest extends TestCase
{
    private PublishPayloadBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new PublishPayloadBuilder(new HtmlRenderer);
    }

    #[Test]
    public function it_builds_generic_payload(): void
    {
        $content = ContentItem::fromApiResponse([
            'id' => 10,
            'slug' => 'test-post',
            'title' => 'Test Post',
            'body' => [
                ['type' => 'paragraph', 'content' => 'Hello.'],
            ],
            'status' => 'published',
            'content_type' => 'article',
        ]);

        $payload = $this->builder->build($content);

        $this->assertSame(10, $payload['contentpulse_id']);
        $this->assertSame('Test Post', $payload['title']);
        $this->assertSame('test-post', $payload['slug']);
        $this->assertStringContainsString('<p>Hello.</p>', $payload['body_html']);
        $this->assertSame('published', $payload['status']);
    }

    #[Test]
    public function it_builds_wordpress_payload_with_correct_post_status(): void
    {
        $content = ContentItem::fromApiResponse([
            'id' => 1,
            'slug' => 'wp-test',
            'title' => 'WP Test',
            'body' => [],
            'status' => 'scheduled',
        ]);

        $payload = $this->builder->buildForWordPress($content);

        $this->assertSame('future', $payload['post_status']);
    }

    #[Test]
    public function it_builds_shopify_payload_with_published_flag(): void
    {
        $content = ContentItem::fromApiResponse([
            'id' => 1,
            'slug' => 'shopify-test',
            'title' => 'Shopify Test',
            'body' => [],
            'status' => 'published',
        ]);

        $payload = $this->builder->buildForShopify($content);

        $this->assertTrue($payload['published']);
    }
}
