<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\File;

use App\Domain\File\Entity\FileDeletionNotification;
use App\Domain\File\ValueObject\DeletionReason;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FileDeletionNotificationTest extends TestCase
{
    public function test_it_accepts_a_valid_deletion_message(): void
    {
        $notification = new FileDeletionNotification(
            recipient: 'developer@example.com',
            fileId: 'file-1',
            originalName: 'contract.docx',
            reason: DeletionReason::RetentionExpired,
            deletedAt: new DateTimeImmutable,
        );

        self::assertSame('retention_expired', $notification->reason->value);
    }

    public function test_it_rejects_an_invalid_recipient(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FileDeletionNotification(
            recipient: 'invalid-address',
            fileId: 'file-1',
            originalName: 'contract.docx',
            reason: DeletionReason::Manual,
            deletedAt: new DateTimeImmutable,
        );
    }
}
