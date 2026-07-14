<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class FileRetentionScheduleTest extends TestCase
{
    public function test_expired_file_cleanup_is_scheduled_every_minute_without_overlap(): void
    {
        self::assertSame(0, Artisan::call('schedule:list'));

        $event = collect($this->app->make(Schedule::class)->events())
            ->first(
                static fn (CallbackEvent $event): bool => $event->description === 'files:delete-expired',
            );

        self::assertInstanceOf(CallbackEvent::class, $event);
        self::assertSame('* * * * *', $event->expression);
        self::assertTrue($event->withoutOverlapping);
        self::assertSame(10, $event->expiresAt);
    }
}
