<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Messaging\RabbitMq;

use App\Domain\File\Entity\FileDeletionNotification;
use App\Domain\File\ValueObject\DeletionReason;
use App\Infrastructure\Messaging\RabbitMq\FileDeletionNotificationSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FileDeletionNotificationSerializerTest extends TestCase
{
    public function test_it_serializes_a_versioned_framework_neutral_event(): void
    {
        $payload = json_decode(
            (new FileDeletionNotificationSerializer)->serialize(
                new FileDeletionNotification(
                    recipient: 'developer@example.com',
                    fileId: 'file-1',
                    originalName: 'contract.pdf',
                    reason: DeletionReason::Manual,
                    deletedAt: new DateTimeImmutable('2026-07-14T12:00:00.123456+00:00'),
                ),
                eventId: 'event-1',
            ),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame([
            'event_id' => 'event-1',
            'event_name' => 'file.deleted.v1',
            'occurred_at' => '2026-07-14T12:00:00.123456+00:00',
            'data' => [
                'file_id' => 'file-1',
                'original_name' => 'contract.pdf',
                'recipient' => 'developer@example.com',
                'reason' => 'manual',
                'deleted_at' => '2026-07-14T12:00:00.123456+00:00',
            ],
        ], $payload);
    }
}
