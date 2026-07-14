<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\RabbitMq;

use App\Domain\File\Contract\FileDeletionNotificationPublisher;
use App\Domain\File\Entity\FileDeletionNotification;
use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;

final readonly class RabbitMqFileDeletionNotificationPublisher implements FileDeletionNotificationPublisher
{
    public function __construct(
        private RabbitMqMessagePublisher $messagePublisher,
        private RabbitMqTopology $topology,
        private FileDeletionNotificationSerializer $serializer,
        private string $appId,
    ) {}

    public function publish(FileDeletionNotification $notification): void
    {
        $eventId = (string) Str::uuid();
        $this->messagePublisher->publish(
            message: new AMQPMessage(
                $this->serializer->serialize($notification, $eventId),
                [
                    'app_id' => $this->appId,
                    'content_encoding' => 'utf-8',
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'message_id' => $eventId,
                    'timestamp' => $notification->deletedAt->getTimestamp(),
                    'type' => FileDeletionNotificationSerializer::EVENT_NAME,
                ],
            ),
            exchange: $this->topology->exchange,
            routingKey: $this->topology->fileDeletedRoutingKey,
        );
    }
}
