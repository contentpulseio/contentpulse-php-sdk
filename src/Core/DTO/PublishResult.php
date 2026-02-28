<?php

declare(strict_types=1);

namespace ContentPulse\Core\DTO;

final class PublishResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $platform,
        public readonly ?string $externalId = null,
        public readonly ?string $externalUrl = null,
        public readonly ?string $message = null,
        public readonly array $metadata = [],
    ) {}

    public static function success(
        string $platform,
        string $externalId,
        ?string $externalUrl = null,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            platform: $platform,
            externalId: $externalId,
            externalUrl: $externalUrl,
            metadata: $metadata,
        );
    }

    public static function failure(string $platform, string $message, array $metadata = []): self
    {
        return new self(
            success: false,
            platform: $platform,
            message: $message,
            metadata: $metadata,
        );
    }
}
