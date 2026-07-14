<?php

declare(strict_types=1);

namespace App\Domain\File\Entity;

use App\Domain\File\ValueObject\DeletionReason;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class FileDeletionNotification
{
    public function __construct(
        public string $recipient,
        public string $fileId,
        public string $originalName,
        public DeletionReason $reason,
        public DateTimeImmutable $deletedAt,
    ) {
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Notification recipient must be a valid email address.');
        }

        if (trim($fileId) === '' || trim($originalName) === '') {
            throw new InvalidArgumentException('Deleted file identity cannot be empty.');
        }
    }
}
