<?php

declare(strict_types=1);

namespace ContentPulse\Core\DTO;

use DateTimeImmutable;

/**
 * Represents the publication tracking record aligned with
 * the `content_publications` table in the ContentPulse platform.
 *
 * Platforms (WordPress, Shopify, etc.) report back this structure
 * so the main platform can track multi-platform publication state.
 */
final class PublicationRecord
{
    public function __construct(
        public readonly int $contentId,
        public readonly string $platform,
        public readonly ?string $remoteId = null,
        public readonly ?string $remoteUrl = null,
        public readonly string $status = 'pending',
        public readonly ?DateTimeImmutable $lastSyncedAt = null,
        public readonly ?string $errorMessage = null,
    ) {}

    /**
     * Build from a PublishResult after a publish operation completes.
     */
    public static function fromPublishResult(int $contentId, PublishResult $result): self
    {
        return new self(
            contentId: $contentId,
            platform: $result->platform,
            remoteId: $result->externalId,
            remoteUrl: $result->externalUrl,
            status: $result->success ? 'synced' : 'failed',
            lastSyncedAt: new DateTimeImmutable,
            errorMessage: $result->success ? null : $result->message,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'content_id' => $this->contentId,
            'platform' => $this->platform,
            'remote_id' => $this->remoteId,
            'remote_url' => $this->remoteUrl,
            'status' => $this->status,
            'last_synced_at' => $this->lastSyncedAt?->format('Y-m-d H:i:s'),
            'error_message' => $this->errorMessage,
        ];
    }

    /**
     * @return array<string, mixed> Payload to report back to the ContentPulse API
     */
    public function toReportPayload(): array
    {
        return array_filter($this->toArray(), fn ($v) => $v !== null);
    }
}
