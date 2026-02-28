<?php

declare(strict_types=1);

namespace ContentPulse\Rendering;

use ContentPulse\Core\DTO\ContentItem;

/**
 * Renders SEO meta tags and structured data from ContentPulse content.
 */
class SeoMetaRenderer
{
    /**
     * Render all meta tags for a content item's <head> section.
     */
    public function renderMetaTags(ContentItem $content, string $siteUrl = ''): string
    {
        if (! $content->seo) {
            return '';
        }

        return $content->seo->toHtml($siteUrl);
    }

    /**
     * Render JSON-LD Article structured data for a content item.
     */
    public function renderArticleSchema(
        ContentItem $content,
        string $siteUrl = '',
        ?string $authorName = null,
        ?string $publisherName = null,
        ?string $publisherLogo = null,
    ): string {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $content->seo?->metaTitle ?? $content->title,
            'description' => $content->seo?->metaDescription ?? $content->excerpt ?? '',
        ];

        if ($content->featuredImage) {
            $schema['image'] = $content->featuredImage;
        }

        if ($content->publishedAt) {
            $schema['datePublished'] = $content->publishedAt->format('c');
        }

        if ($content->updatedAt) {
            $schema['dateModified'] = $content->updatedAt->format('c');
        }

        if ($content->slug && $siteUrl) {
            $schema['url'] = rtrim($siteUrl, '/').'/'.ltrim($content->slug, '/');
        }

        if ($authorName) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $authorName,
            ];
        }

        if ($publisherName) {
            $publisher = [
                '@type' => 'Organization',
                'name' => $publisherName,
            ];
            if ($publisherLogo) {
                $publisher['logo'] = [
                    '@type' => 'ImageObject',
                    'url' => $publisherLogo,
                ];
            }
            $schema['publisher'] = $publisher;
        }

        if ($content->wordCount) {
            $schema['wordCount'] = $content->wordCount;
        }

        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return "<script type=\"application/ld+json\">\n{$json}\n</script>";
    }
}
