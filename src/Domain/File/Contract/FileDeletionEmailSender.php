<?php

declare(strict_types=1);

namespace App\Domain\File\Contract;

use App\Domain\File\Entity\FileDeletionNotification;

interface FileDeletionEmailSender
{
    public function send(FileDeletionNotification $notification): void;
}
