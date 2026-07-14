<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Messaging\RabbitMq;

use App\Domain\File\Entity\FileDeletionNotification;
use App\Domain\File\ValueObject\DeletionReason;
use App\Infrastructure\Messaging\RabbitMq\Exception\InvalidRabbitMqMessage;
use App\Infrastructure\Messaging\RabbitMq\FileDeletionNotificationDeserializer;
use App\Infrastructure\Messaging\RabbitMq\FileDeletionNotificationSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FileDeletionNotificationDeserializerTest extends TestCase
{
    public function test_it_reconstitutes_the_serialized_domain_notification(): void
    {
        $notification = new FileDeletionNotification(
            recipient: 'developer@example.com',
            fileId: 'file-1',
            originalName: 'contract.pdf',
            reason: DeletionReason::RetentionExpired,
            deletedAt: new DateTimeImmutable('2026-07-14T12:00:00.123456+00:00'),
        );
        $messageBody = (new FileDeletionNotificationSerializer)->serialize($notification, 'event-1');

        $restoredNotification = (new FileDeletionNotificationDeserializer)->deserialize($messageBody);

        self::assertEquals($notification, $restoredNotification);
    }

    public function test_it_rejects_an_unknown_event_schema(): void
    {
        $this->expectException(InvalidRabbitMqMessage::class);

        (new FileDeletionNotificationDeserializer)->deserialize(json_encode([
            'event_name' => 'file.deleted.v2',
            'data' => [],
        ], JSON_THROW_ON_ERROR));
    }
}
