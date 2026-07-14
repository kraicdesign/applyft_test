<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Messaging\RabbitMq;

use App\Domain\File\Contract\FileDeletionNotificationPublisher;
use App\Domain\File\Entity\FileDeletionNotification;
use App\Domain\File\ValueObject\DeletionReason;
use App\Infrastructure\Messaging\RabbitMq\FileDeletionNotificationSerializer;
use App\Infrastructure\Messaging\RabbitMq\RabbitMqConnectionFactory;
use App\Infrastructure\Messaging\RabbitMq\RabbitMqFileDeletionNotificationPublisher;
use App\Infrastructure\Messaging\RabbitMq\RabbitMqFileDeletionNotificationReceiver;
use DateTimeImmutable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Mailer as IlluminateMailer;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

final class RabbitMqFileDeletionNotificationPublisherTest extends TestCase
{
    public function test_it_publishes_a_persistent_file_deletion_event_to_rabbitmq(): void
    {
        $testSuffix = strtolower(Str::random(12));
        $exchange = sprintf('file.events.test.%s', $testSuffix);
        $queue = sprintf('email.file-deleted.test.%s', $testSuffix);
        $routingKey = 'file.deleted';

        config([
            'rabbitmq.exchange' => $exchange,
            'rabbitmq.file_deleted_queue' => $queue,
            'rabbitmq.file_deleted_routing_key' => $routingKey,
            'rabbitmq.app_id' => 'file-retention-test',
        ]);

        $publisher = $this->app->make(FileDeletionNotificationPublisher::class);
        self::assertInstanceOf(RabbitMqFileDeletionNotificationPublisher::class, $publisher);

        $deletedAt = new DateTimeImmutable('2026-07-14T12:00:00+00:00');
        $publisher->publish(new FileDeletionNotification(
            recipient: 'developer@example.com',
            fileId: 'file-1',
            originalName: 'contract.pdf',
            reason: DeletionReason::RetentionExpired,
            deletedAt: $deletedAt,
        ));

        $connection = $this->app->make(RabbitMqConnectionFactory::class)->createConnection();
        $channel = $connection->channel();

        try {
            $message = $channel->basic_get($queue, true);

            self::assertNotNull($message);
            self::assertSame('application/json', $message->get('content_type'));
            self::assertSame('utf-8', $message->get('content_encoding'));
            self::assertSame(2, $message->get('delivery_mode'));
            self::assertSame(FileDeletionNotificationSerializer::EVENT_NAME, $message->get('type'));
            self::assertSame('file-retention-test', $message->get('app_id'));

            $payload = json_decode($message->getBody(), true, flags: JSON_THROW_ON_ERROR);

            self::assertSame($message->get('message_id'), $payload['event_id']);
            self::assertSame('file.deleted.v1', $payload['event_name']);
            self::assertSame('file-1', $payload['data']['file_id']);
            self::assertSame('developer@example.com', $payload['data']['recipient']);
            self::assertSame('retention_expired', $payload['data']['reason']);
        } finally {
            $channel->queue_delete($queue);
            $channel->exchange_delete($exchange);
            $connection->close();
        }
    }

    public function test_it_receives_the_event_and_sends_it_through_laravel_mail(): void
    {
        $testSuffix = strtolower(Str::random(12));
        $exchange = sprintf('file.events.test.%s', $testSuffix);
        $queue = sprintf('email.file-deleted.test.%s', $testSuffix);

        config([
            'rabbitmq.exchange' => $exchange,
            'rabbitmq.file_deleted_queue' => $queue,
            'rabbitmq.file_deleted_routing_key' => 'file.deleted',
        ]);

        $notification = new FileDeletionNotification(
            recipient: 'developer@example.com',
            fileId: 'file-2',
            originalName: 'report.docx',
            reason: DeletionReason::Manual,
            deletedAt: new DateTimeImmutable('2026-07-14T13:00:00+00:00'),
        );
        $this->app->make(FileDeletionNotificationPublisher::class)->publish($notification);

        $receivedMessageCount = $this->app
            ->make(RabbitMqFileDeletionNotificationReceiver::class)
            ->receive(maximumMessages: 1, pollIntervalMilliseconds: 1);

        self::assertSame(1, $receivedMessageCount);

        $mailer = $this->app->make(Mailer::class);
        self::assertInstanceOf(IlluminateMailer::class, $mailer);
        $transport = $mailer->getSymfonyTransport();
        self::assertInstanceOf(ArrayTransport::class, $transport);
        $sentMessage = $transport->messages()->last();
        self::assertNotNull($sentMessage);
        $email = $sentMessage->getOriginalMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('File deleted: report.docx', $email->getSubject());
        self::assertSame('developer@example.com', $email->getTo()[0]->getAddress());
        self::assertStringContainsString('Reason: manual', (string) $email->getTextBody());

        $connection = $this->app->make(RabbitMqConnectionFactory::class)->createConnection();
        $channel = $connection->channel();

        try {
            self::assertNull($channel->basic_get($queue, true));
        } finally {
            $channel->queue_delete($queue);
            $channel->exchange_delete($exchange);
            $connection->close();
        }
    }
}
