<?php

declare(strict_types=1);

namespace ContentPulse\Core\DTO;

/**
 * Represents the version handshake / compatibility check result
 * between a platform plugin and the ContentPulse API.
 */
final class CompatibilityInfo
{
    /** @var string[] */
    private const VALID_PLATFORMS = ['wordpress', 'shopify', 'custom'];

    public function __construct(
        public readonly string $platform,
        public readonly string $pluginVersion,
        public readonly string $sdkVersion,
        public readonly bool $compatible,
        public readonly ?string $message = null,
        public readonly array $capabilities = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'platform' => $this->platform,
            'plugin_version' => $this->pluginVersion,
            'sdk_version' => $this->sdkVersion,
            'compatible' => $this->compatible,
            'message' => $this->message,
            'capabilities' => $this->capabilities,
        ];
    }

    public static function isValidPlatform(string $platform): bool
    {
        return in_array($platform, self::VALID_PLATFORMS, true);
    }

    /**
     * @return string[]
     */
    public static function getValidPlatforms(): array
    {
        return self::VALID_PLATFORMS;
    }
}
