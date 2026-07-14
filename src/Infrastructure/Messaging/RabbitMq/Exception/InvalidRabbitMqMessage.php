<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\RabbitMq\Exception;

use RuntimeException;
use Throwable;

final class InvalidRabbitMqMessage extends RuntimeException
{
    public static function becausePayloadIsInvalid(?Throwable $previous = null): self
    {
        return new self('RabbitMQ file-deletion message payload is invalid.', previous: $previous);
    }
}
