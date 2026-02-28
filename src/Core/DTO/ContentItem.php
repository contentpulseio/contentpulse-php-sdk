<?php

declare(strict_types=1);

namespace ContentPulse\Core\DTO;

use DateTimeImmutable;

final class ContentItem
{
    /**
     * @param  Section[]  $sections  Normalized body sections
     * @param  array<string, mixed>  $categories
     * @param  array<string, mixed>  $tags
     * @param  array<string, mixed>  $images  Image variant URLs
     * @param  array<string, mixed>  $raw  Original API response data
     */
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $title,
        public readonly array $sections,
        public readonly ?string $excerpt = null,
        public readonly ?string $featuredImage = null,
        public readonly array $images = [],
        public readonly ?SeoMeta $seo = null,
        public readonly ?string $status = null,
        public readonly ?string $contentType = null,
        public readonly ?string $locale = null,
        public readonly ?int $wordCount = null,
        public readonly array $categories = [],
        public readonly array $tags = [],
        public readonly ?DateTimeImmutable $publishedAt = null,
        public readonly ?DateTimeImmutable $scheduledAt = null,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly ?DateTimeImmutable $updatedAt = null,
        public readonly array $raw = [],
    ) {}

    /**
     * Build from a ContentPulse API response payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        $body = $data['body'] ?? [];
        $normalizer = new \ContentPulse\Rendering\SectionNormalizer;
        $sections = $normalizer->normalize($body);

        $seo = SeoMeta::fromArray($data);

        return new self(
            id: (int) ($data['id'] ?? 0),
            slug: $data['slug'] ?? '',
            title: $data['title'] ?? '',
            sections: $sections,
            excerpt: $data['excerpt'] ?? null,
            featuredImage: $data['featured_image'] ?? null,
            images: $data['image_variants'] ?? [],
            seo: $seo,
            status: $data['status'] ?? null,
            contentType: $data['content_type'] ?? null,
            locale: $data['locale'] ?? null,
            wordCount: isset($data['word_count']) ? (int) $data['word_count'] : null,
            categories: $data['categories'] ?? [],
            tags: $data['tags'] ?? [],
            publishedAt: self::parseDate($data['published_at'] ?? null),
            scheduledAt: self::parseDate($data['scheduled_at'] ?? null),
            createdAt: self::parseDate($data['created_at'] ?? null),
            updatedAt: self::parseDate($data['updated_at'] ?? null),
            raw: $data,
        );
    }

    private static function parseDate(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u\Z', $value)
            ?: DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $value)
            ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

        return $date ?: null;
    }
}
