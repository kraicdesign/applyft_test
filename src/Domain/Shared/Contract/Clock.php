<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contract;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
