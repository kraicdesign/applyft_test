<?php

declare(strict_types=1);

namespace App\Domain\File\ValueObject;

enum DeletionReason: string
{
    case Manual = 'manual';
    case RetentionExpired = 'retention_expired';
}
