<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\RabbitMq;

use App\Application\File\Consumer\FileDeletionNotificationConsumer;
use App\Infrastructure\Messaging\RabbitMq\Exception\InvalidRabbitMqMessage;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

final class RabbitMqFileDeletionNotificationReceiver
{
    private bool $stopRequested = false;

    public function __construct(
        private readonly RabbitMqConnectionFactory $connectionFactory,
        private readonly RabbitMqTopology $topology,
        private readonly FileDeletionNotificationDeserializer $deserializer,
        private readonly FileDeletionNotificationConsumer $notificationConsumer,
        private readonly LoggerInterface $logger,
    ) {}

    public function receive(int $maximumMessages = 0, int $pollIntervalMilliseconds = 500): int
    {
        if ($maximumMessages < 0 || $pollIntervalMilliseconds < 1) {
            throw new InvalidArgumentException('Receiver limits and polling interval are invalid.');
        }

        $this->stopRequested = false;
        $receivedMessageCount = 0;
        $connection = $this->connectionFactory->createConnection();
        $channel = $connection->channel();

        try {
            $this->topology->declareFileDeletedTopology($channel);

            while (! $this->stopRequested) {
                $message = $channel->basic_get($this->topology->fileDeletedQueue, false);

                if ($message === null) {
                    if ($maximumMessages > 0) {
                        break;
                    }

                    usleep($pollIntervalMilliseconds * 1000);

                    continue;
                }

                try {
                    $notification = $this->deserializer->deserialize($message->getBody());
                    $this->notificationConsumer->consume($notification);
                    $message->ack();
                    $receivedMessageCount++;
                } catch (InvalidRabbitMqMessage $exception) {
                    $message->reject(false);
                    $this->logger->warning($exception->getMessage(), [
                        'exception' => $exception,
                        'message_id' => $message->has('message_id') ? $message->get('message_id') : null,
                    ]);
                } catch (Throwable $exception) {
                    $message->nack(true);

                    throw $exception;
                }

                if ($maximumMessages > 0 && $receivedMessageCount >= $maximumMessages) {
                    break;
                }
            }

            return $receivedMessageCount;
        } finally {
            try {
                if ($connection->isConnected()) {
                    $connection->close();
                }
            } catch (Throwable) {
                // Closing must not hide the processing result.
            }
        }
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }
}
