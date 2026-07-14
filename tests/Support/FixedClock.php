<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Shared\Contract\Clock;
use DateTimeImmutable;

final readonly class FixedClock implements Clock
{
    public function __construct(private DateTimeImmutable $time) {}

    public function now(): DateTimeImmutable
    {
        return $this->time;
    }
}
