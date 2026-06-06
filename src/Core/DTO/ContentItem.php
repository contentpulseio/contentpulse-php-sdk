<?php

declare(strict_types=1);

namespace ContentPulse\Core\DTO;

use ContentPulse\Rendering\SectionNormalizer;
use DateTimeImmutable;

final class ContentItem
{
    /**
     * @param  Section[]  $sections  Normalized body sections (empty unless the API key is granted the structured body)
     * @param  string|null  $renderedHtml  Server-rendered HTML of the body (authoritative for display)
     * @param  array<int, array{question: string, answer: string}>  $faq  Public FAQ pairs (always present; powers FAQPage schema)
     * @param  array<string, mixed>  $categories
     * @param  array<string, mixed>  $tags
     * @param  array<string, mixed>  $images  Image variant URLs
     * @param  array<string, mixed>  $raw  Original API response data
     */
    public function __construct(
        public readonly string $id,
        public readonly string $slug,
        public readonly string $title,
        public readonly array $sections,
        public readonly ?string $renderedHtml = null,
        public readonly array $faq = [],
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
        $version = is_array($data['current_version'] ?? null) ? $data['current_version'] : [];

        $body = $data['body'] ?? $version['body'] ?? [];
        $normalizer = new SectionNormalizer;
        $sections = $normalizer->normalize($body);

        $renderedHtml = $data['rendered_html'] ?? $version['rendered_html'] ?? null;

        $rawFaq = $data['faq'] ?? $version['faq'] ?? [];
        $faq = self::normalizeFaq(is_array($rawFaq) ? $rawFaq : []);

        $seo = SeoMeta::fromArray($data);

        return new self(
            id: (string) ($data['id'] ?? ''),
            slug: $data['slug'] ?? '',
            title: $data['title'] ?? '',
            sections: $sections,
            renderedHtml: $renderedHtml,
            faq: $faq,
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

    /**
     * @param  array<int|string, mixed>  $raw
     * @return array<int, array{question: string, answer: string}>
     */
    private static function normalizeFaq(array $raw): array
    {
        $faq = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }

            $faq[] = ['question' => $question, 'answer' => $answer];
        }

        return $faq;
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
