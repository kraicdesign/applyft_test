<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\RabbitMq\Console;

use App\Infrastructure\Messaging\RabbitMq\RabbitMqFileDeletionNotificationReceiver;
use Illuminate\Console\Command;

final class ConsumeFileDeletionNotificationsCommand extends Command
{
    protected $signature = 'rabbitmq:consume-file-deletion-notifications
        {--once : Process at most one available message and exit}
        {--poll=500 : Empty-queue polling interval in milliseconds}';

    protected $description = 'Consume file-deletion notifications from RabbitMQ and send their emails';

    public function __construct(private readonly RabbitMqFileDeletionNotificationReceiver $receiver)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $pollInterval = filter_var($this->option('poll'), FILTER_VALIDATE_INT);

        if ($pollInterval === false || $pollInterval < 1) {
            $this->components->error('The polling interval must be a positive integer.');

            return self::INVALID;
        }

        if (defined('SIGINT') && defined('SIGTERM')) {
            $this->trap([SIGINT, SIGTERM], fn (): bool => $this->stopReceiver());
        }

        $receivedMessageCount = $this->receiver->receive(
            maximumMessages: $this->option('once') ? 1 : 0,
            pollIntervalMilliseconds: $pollInterval,
        );

        $this->components->info(sprintf('Processed %d file-deletion notification(s).', $receivedMessageCount));

        return self::SUCCESS;
    }

    private function stopReceiver(): bool
    {
        $this->receiver->requestStop();

        return true;
    }
}
