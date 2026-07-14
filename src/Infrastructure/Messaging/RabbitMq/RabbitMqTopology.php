<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;

final readonly class RabbitMqTopology
{
    public function __construct(
        public string $exchange,
        public string $fileDeletedQueue,
        public string $fileDeletedRoutingKey,
    ) {}

    public function declareFileDeletedTopology(AMQPChannel $channel): void
    {
        $channel->exchange_declare(
            exchange: $this->exchange,
            type: 'topic',
            passive: false,
            durable: true,
            auto_delete: false,
        );
        $channel->queue_declare(
            queue: $this->fileDeletedQueue,
            passive: false,
            durable: true,
            exclusive: false,
            auto_delete: false,
        );
        $channel->queue_bind(
            queue: $this->fileDeletedQueue,
            exchange: $this->exchange,
            routing_key: $this->fileDeletedRoutingKey,
        );
    }
}
