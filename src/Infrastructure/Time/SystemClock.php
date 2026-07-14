<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

use App\Domain\Shared\Contract\Clock;
use DateTimeImmutable;
use DateTimeZone;

final readonly class SystemClock implements Clock
{
    public function __construct(private DateTimeZone $timezone) {}

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }
}
