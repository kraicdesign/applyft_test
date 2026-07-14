<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\File\Contract\FileDeletionEmailSender;
use App\Domain\File\Entity\FileDeletionNotification;

final class InMemoryFileDeletionEmailSender implements FileDeletionEmailSender
{
    /** @var list<FileDeletionNotification> */
    public array $sentNotifications = [];

    public function send(FileDeletionNotification $notification): void
    {
        $this->sentNotifications[] = $notification;
    }
}
