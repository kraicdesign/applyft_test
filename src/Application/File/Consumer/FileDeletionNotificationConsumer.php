<?php

declare(strict_types=1);

namespace App\Application\File\Consumer;

use App\Domain\File\Contract\FileDeletionEmailSender;
use App\Domain\File\Entity\FileDeletionNotification;

final readonly class FileDeletionNotificationConsumer
{
    public function __construct(private FileDeletionEmailSender $emailSender) {}

    public function consume(FileDeletionNotification $notification): void
    {
        $this->emailSender->send($notification);
    }
}
