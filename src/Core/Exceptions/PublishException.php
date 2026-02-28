<?php

declare(strict_types=1);

namespace ContentPulse\Core\Exceptions;

class PublishException extends ContentPulseException
{
    public function __construct(
        string $message,
        protected readonly string $platform = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }
}
