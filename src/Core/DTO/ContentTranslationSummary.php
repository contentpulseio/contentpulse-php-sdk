<?php

declare(strict_types=1);

namespace ContentPulse\Core\DTO;

use DateTimeImmutable;

/**
 * Status row for one locale of a content item (from GET content/{id}/translations).
 */
final class ContentTranslationSummary
{
    public function __construct(
        public readonly string $locale,
        public readonly string $status,
        public readonly bool $isCurrent,
        public readonly bool $isPublished,
        public readonly bool $isLive,
        public readonly bool $stale,
        public readonly ?int $versionNumber = null,
        public readonly ?DateTimeImmutable $translatedAt = null,
        public readonly ?string $liveUrl = null,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            locale: (string) ($data['locale'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            isCurrent: (bool) ($data['is_current'] ?? false),
            isPublished: (bool) ($data['is_published'] ?? false),
            isLive: (bool) ($data['is_live'] ?? false),
            stale: (bool) ($data['stale'] ?? false),
            versionNumber: isset($data['version_number']) ? (int) $data['version_number'] : null,
            translatedAt: self::parseDate($data['translated_at'] ?? null),
            liveUrl: isset($data['live_url']) && is_string($data['live_url']) ? $data['live_url'] : null,
            raw: $data,
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
