<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\File\Contract\FileDeletionNotificationPublisher;
use App\Domain\File\Entity\FileDeletionNotification;

final class InMemoryFileDeletionNotificationPublisher implements FileDeletionNotificationPublisher
{
    /** @var list<FileDeletionNotification> */
    public array $published = [];

    public function publish(FileDeletionNotification $notification): void
    {
        $this->published[] = $notification;
    }
}
