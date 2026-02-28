<?php

declare(strict_types=1);

namespace ContentPulse\Tests\Unit\Core;

use ContentPulse\Core\DTO\ContentItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContentItemTest extends TestCase
{
    #[Test]
    public function it_creates_from_api_response(): void
    {
        $data = [
            'id' => 42,
            'slug' => 'test-article',
            'title' => 'Test Article',
            'body' => [
                ['type' => 'heading', 'content' => 'Intro', 'attributes' => ['level' => 2]],
                ['type' => 'paragraph', 'content' => 'Hello world.'],
            ],
            'excerpt' => 'A test article.',
            'featured_image' => 'https://example.com/img.jpg',
            'status' => 'published',
            'content_type' => 'article',
            'locale' => 'en',
            'word_count' => 150,
            'meta_title' => 'Test SEO Title',
            'meta_description' => 'Test SEO Description',
            'published_at' => '2026-02-28 12:00:00',
            'created_at' => '2026-02-27 10:00:00',
            'categories' => [['id' => 1, 'name' => 'Tech']],
            'tags' => [['id' => 1, 'name' => 'PHP']],
        ];

        $item = ContentItem::fromApiResponse($data);

        $this->assertSame(42, $item->id);
        $this->assertSame('test-article', $item->slug);
        $this->assertSame('Test Article', $item->title);
        $this->assertCount(2, $item->sections);
        $this->assertSame('heading', $item->sections[0]->type);
        $this->assertSame('Intro', $item->sections[0]->content);
        $this->assertSame('A test article.', $item->excerpt);
        $this->assertSame('published', $item->status);
        $this->assertSame(150, $item->wordCount);
        $this->assertNotNull($item->seo);
        $this->assertSame('Test SEO Title', $item->seo->metaTitle);
        $this->assertNotNull($item->publishedAt);
    }

    #[Test]
    public function it_handles_empty_body(): void
    {
        $data = [
            'id' => 1,
            'slug' => 'empty',
            'title' => 'Empty',
            'body' => [],
        ];

        $item = ContentItem::fromApiResponse($data);

        $this->assertSame([], $item->sections);
    }

    #[Test]
    public function it_handles_missing_optional_fields(): void
    {
        $data = [
            'id' => 1,
            'slug' => 'minimal',
            'title' => 'Minimal',
        ];

        $item = ContentItem::fromApiResponse($data);

        $this->assertSame('minimal', $item->slug);
        $this->assertNull($item->publishedAt);
        $this->assertNull($item->excerpt);
        $this->assertSame([], $item->sections);
    }
}
