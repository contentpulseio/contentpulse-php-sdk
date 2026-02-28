<?php

declare(strict_types=1);

namespace ContentPulse\Core\Exceptions;

class AuthenticationException extends ApiException
{
    public function __construct(string $message = 'Invalid or missing API key.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, [], $previous);
    }
}
