<?php

declare(strict_types=1);

namespace ContentPulse\Core\Exceptions;

class ApiException extends ContentPulseException
{
    public function __construct(
        string $message,
        protected readonly int $statusCode = 0,
        protected readonly array $responseBody = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): array
    {
        return $this->responseBody;
    }
}
