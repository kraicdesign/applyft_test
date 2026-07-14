<?php

declare(strict_types=1);

namespace App\Presentation\Scheduling;

use App\Application\File\Command\DeleteExpiredFiles\DeleteExpiredFilesCommand;
use App\Application\File\Command\DeleteExpiredFiles\DeleteExpiredFilesHandler;
use Illuminate\Console\Scheduling\Schedule;

final readonly class FileRetentionSchedule
{
    public function __invoke(Schedule $schedule): void
    {
        $schedule
            ->call(
                static fn (DeleteExpiredFilesHandler $handler): int => $handler->handle(
                    new DeleteExpiredFilesCommand(now()->toDateTimeImmutable()),
                ),
            )
            ->name('files:delete-expired')
            ->everyMinute()
            ->withoutOverlapping(10);
    }
}
