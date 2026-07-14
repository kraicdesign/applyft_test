<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\RabbitMq;

use App\Domain\File\Entity\FileDeletionNotification;
use JsonException;

final readonly class FileDeletionNotificationSerializer
{
    public const string EVENT_NAME = 'file.deleted.v1';

    /** @throws JsonException */
    public function serialize(FileDeletionNotification $notification, string $eventId): string
    {
        return json_encode([
            'event_id' => $eventId,
            'event_name' => self::EVENT_NAME,
            'occurred_at' => $notification->deletedAt->format('Y-m-d\TH:i:s.uP'),
            'data' => [
                'file_id' => $notification->fileId,
                'original_name' => $notification->originalName,
                'recipient' => $notification->recipient,
                'reason' => $notification->reason->value,
                'deleted_at' => $notification->deletedAt->format('Y-m-d\TH:i:s.uP'),
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
