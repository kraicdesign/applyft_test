<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\RabbitMq;

use App\Infrastructure\Messaging\RabbitMq\Exception\RabbitMqPublishFailed;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

final readonly class RabbitMqMessagePublisher
{
    public function __construct(
        private RabbitMqConnectionFactory $connectionFactory,
        private RabbitMqTopology $topology,
        private float $publishTimeout,
    ) {}

    public function publish(AMQPMessage $message, string $exchange, string $routingKey): void
    {
        $connection = $this->connectionFactory->createConnection();
        $channel = $connection->channel();
        $rejected = false;
        $unroutableMessage = null;

        try {
            $this->topology->declareFileDeletedTopology($channel);
            $channel->confirm_select();
            $channel->set_nack_handler(static function () use (&$rejected): void {
                $rejected = true;
            });
            $channel->set_return_listener(static function (
                int $replyCode,
                string $replyText,
                string $returnedExchange,
                string $returnedRoutingKey,
            ) use (&$unroutableMessage): void {
                $unroutableMessage = RabbitMqPublishFailed::becauseMessageWasUnroutable(
                    $replyCode,
                    $replyText,
                    $returnedExchange,
                    $returnedRoutingKey,
                );
            });

            $channel->basic_publish(
                msg: $message,
                exchange: $exchange,
                routing_key: $routingKey,
                mandatory: true,
            );
            $channel->wait_for_pending_acks_returns($this->publishTimeout);

            if ($unroutableMessage instanceof RabbitMqPublishFailed) {
                throw $unroutableMessage;
            }

            if ($rejected) {
                throw RabbitMqPublishFailed::becauseMessageWasRejected();
            }
        } finally {
            try {
                if ($connection->isConnected()) {
                    $connection->close();
                }
            } catch (Throwable) {
                // Closing must not hide the original publishing result.
            }
        }
    }
}
