<?php

declare(strict_types=1);

namespace Tests\Unit\Application\File;

use App\Application\File\Consumer\FileDeletionNotificationConsumer;
use App\Domain\File\Entity\FileDeletionNotification;
use App\Domain\File\ValueObject\DeletionReason;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryFileDeletionEmailSender;

final class FileDeletionNotificationConsumerTest extends TestCase
{
    public function test_it_sends_a_received_deletion_notification_to_email(): void
    {
        $emailSender = new InMemoryFileDeletionEmailSender;
        $notification = new FileDeletionNotification(
            recipient: 'developer@example.com',
            fileId: 'file-1',
            originalName: 'contract.pdf',
            reason: DeletionReason::Manual,
            deletedAt: new DateTimeImmutable('2026-07-14T12:00:00+00:00'),
        );

        (new FileDeletionNotificationConsumer($emailSender))->consume($notification);

        self::assertSame([$notification], $emailSender->sentNotifications);
    }
}
