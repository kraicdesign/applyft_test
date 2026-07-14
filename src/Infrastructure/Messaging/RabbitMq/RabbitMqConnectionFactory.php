<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\RabbitMq;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory;

final readonly class RabbitMqConnectionFactory
{
    public function __construct(
        private string $host,
        private int $port,
        private string $user,
        private string $password,
        private string $vhost,
        private float $connectionTimeout,
        private float $readWriteTimeout,
        private int $heartbeat,
    ) {}

    public function createConnection(): AbstractConnection
    {
        $configuration = new AMQPConnectionConfig;
        $configuration->setIoType(AMQPConnectionConfig::IO_TYPE_STREAM);
        $configuration->setHost($this->host);
        $configuration->setPort($this->port);
        $configuration->setUser($this->user);
        $configuration->setPassword($this->password);
        $configuration->setVhost($this->vhost);
        $configuration->setConnectionTimeout($this->connectionTimeout);
        $configuration->setReadTimeout($this->readWriteTimeout);
        $configuration->setWriteTimeout($this->readWriteTimeout);
        $configuration->setChannelRPCTimeout($this->connectionTimeout);
        $configuration->setHeartbeat($this->heartbeat);

        return AMQPConnectionFactory::create($configuration);
    }
}
