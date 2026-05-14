<?php

declare(strict_types=1);

namespace ContentPulse\Core\Exceptions;

use Throwable;

class NotFoundException extends ApiException
{
    public function __construct(string $message = 'Resource not found.', ?Throwable $previous = null)
    {
        parent::__construct($message, 404, [], $previous);
    }
}
