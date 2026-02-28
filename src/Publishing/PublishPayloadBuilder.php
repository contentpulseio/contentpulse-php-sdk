<?php

declare(strict_types=1);

namespace ContentPulse\Publishing;

use ContentPulse\Core\Contracts\SectionRendererInterface;
use ContentPulse\Core\DTO\ContentItem;

/**
 * Builds platform-agnostic publication payloads from ContentItem DTOs.
 *
 * Consumers (WordPress plugin, Shopify app) can use this to prepare
 * the data they need to create/update posts or articles on their platform.
 */
class PublishPayloadBuilder
{
    public function __construct(
        private readonly SectionRendererInterface $renderer,
    ) {}

    /**
     * Build a generic payload suitable for most CMS platforms.
     *
     * @return array<string, mixed>
     */
    public function build(ContentItem $content): array
    {
        $html = $this->renderer->renderAll($content->sections);

        return [
            'contentpulse_id' => $content->id,
            'title' => $content->title,
            'slug' => $content->slug,
            'body_html' => $html,
            'excerpt' => $content->excerpt ?? '',
            'featured_image' => $content->featuredImage,
            'image_variants' => $content->images,
            'seo' => $content->seo?->toArray() ?? [],
            'categories' => $content->categories,
            'tags' => $content->tags,
            'status' => $content->status,
            'content_type' => $content->contentType,
            'locale' => $content->locale,
            'word_count' => $content->wordCount,
            'published_at' => $content->publishedAt?->format('Y-m-d H:i:s'),
            'scheduled_at' => $content->scheduledAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Build a WordPress-specific payload with block markup.
     *
     * @return array<string, mixed>
     */
    public function buildForWordPress(ContentItem $content): array
    {
        $base = $this->build($content);

        $base['post_status'] = match ($content->status) {
            'published' => 'publish',
            'draft' => 'draft',
            'scheduled' => 'future',
            default => 'draft',
        };

        return $base;
    }

    /**
     * Build a Shopify-specific payload for blog article or page.
     *
     * @return array<string, mixed>
     */
    public function buildForShopify(ContentItem $content): array
    {
        $base = $this->build($content);

        $base['published'] = $content->status === 'published';

        return $base;
    }
}
