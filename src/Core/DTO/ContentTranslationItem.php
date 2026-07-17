<?php

declare(strict_types=1);

namespace ContentPulse\Core\DTO;

use ContentPulse\Rendering\SectionNormalizer;
use DateTimeImmutable;

/**
 * Full translated payload for one locale (from GET content/{id}/translations/{locale}).
 */
final class ContentTranslationItem
{
    /**
     * @param  Section[]  $sections
     * @param  array<string, mixed>  $images
     * @param  array<string, mixed>  $categories
     * @param  array<string, mixed>  $tags
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $locale,
        public readonly string $status,
        public readonly string $title,
        public readonly array $sections,
        public readonly ?string $excerpt = null,
        public readonly ?string $featuredImage = null,
        public readonly array $images = [],
        public readonly ?SeoMeta $seo = null,
        public readonly ?int $wordCount = null,
        public readonly bool $isCurrent = true,
        public readonly ?int $versionNumber = null,
        public readonly ?DateTimeImmutable $translatedAt = null,
        public readonly array $categories = [],
        public readonly array $tags = [],
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        $body = $data['body'] ?? [];
        $normalizer = new SectionNormalizer;
        $sections = $normalizer->normalize(is_array($body) ? $body : []);

        $seo = SeoMeta::fromArray([
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'og_title' => $data['og_title'] ?? null,
            'og_description' => $data['og_description'] ?? null,
            'twitter_title' => $data['twitter_title'] ?? null,
            'twitter_description' => $data['twitter_description'] ?? null,
        ]);

        $featuredImage = $data['featured_image_url'] ?? $data['featured_image'] ?? null;
        $imageVariants = $data['image_variants'] ?? [];
        if (! is_array($imageVariants)) {
            $imageVariants = [];
        }

        return new self(
            locale: (string) ($data['locale'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            sections: $sections,
            excerpt: isset($data['excerpt']) && is_string($data['excerpt']) ? $data['excerpt'] : null,
            featuredImage: is_string($featuredImage) ? $featuredImage : null,
            images: $imageVariants,
            seo: $seo,
            wordCount: isset($data['word_count']) ? (int) $data['word_count'] : null,
            isCurrent: (bool) ($data['is_current'] ?? true),
            versionNumber: isset($data['version_number']) ? (int) $data['version_number'] : null,
            translatedAt: self::parseDate($data['translated_at'] ?? null),
            categories: is_array($data['categories'] ?? null) ? $data['categories'] : [],
            tags: is_array($data['tags'] ?? null) ? $data['tags'] : [],
            raw: $data,
        );
    }

    /**
     * Build a ContentItem suitable for Laravel package upsert.
     *
     * external id = "{parentUlid}__{locale}" so translations never collide with
     * the source-language Content row.
     */
    public function toContentItem(ContentItem $parent): ContentItem
    {
        $externalId = $parent->id.'__'.$this->locale;

        return new ContentItem(
            id: $externalId,
            slug: $parent->slug,
            title: $this->title !== '' ? $this->title : $parent->title,
            sections: $this->sections !== [] ? $this->sections : $parent->sections,
            renderedHtml: null,
            faq: $parent->faq,
            excerpt: $this->excerpt ?? $parent->excerpt,
            featuredImage: $this->featuredImage ?? $parent->featuredImage,
            images: $this->images !== [] ? $this->images : $parent->images,
            seo: $this->seo ?? $parent->seo,
            status: $parent->status,
            contentType: $parent->contentType,
            locale: $this->locale,
            wordCount: $this->wordCount ?? $parent->wordCount,
            categories: $this->categories !== [] ? $this->categories : $parent->categories,
            tags: $this->tags !== [] ? $this->tags : $parent->tags,
            publishedAt: $parent->publishedAt,
            scheduledAt: $parent->scheduledAt,
            createdAt: $parent->createdAt,
            updatedAt: $this->translatedAt ?? $parent->updatedAt,
            raw: array_merge($parent->raw, [
                'locale' => $this->locale,
                'body' => $this->raw['body'] ?? [],
                'parent_external_id' => $parent->id,
                'translation' => $this->raw,
            ]),
        );
    }

    private static function parseDate(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
