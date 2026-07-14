<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\RabbitMq\Exception;

use RuntimeException;

final class RabbitMqPublishFailed extends RuntimeException
{
    public static function becauseMessageWasRejected(): self
    {
        return new self('RabbitMQ rejected the file deletion event.');
    }

    public static function becauseMessageWasUnroutable(
        int $replyCode,
        string $replyText,
        string $exchange,
        string $routingKey,
    ): self {
        return new self(sprintf(
            'RabbitMQ returned unroutable message (%d %s) from exchange "%s" with routing key "%s".',
            $replyCode,
            $replyText,
            $exchange,
            $routingKey,
        ));
    }
}
