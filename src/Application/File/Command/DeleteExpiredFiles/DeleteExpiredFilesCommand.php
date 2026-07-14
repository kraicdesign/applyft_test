<?php

declare(strict_types=1);

namespace App\Application\File\Command\DeleteExpiredFiles;

use DateTimeImmutable;

final readonly class DeleteExpiredFilesCommand
{
    public function __construct(public DateTimeImmutable $asOf) {}
}
