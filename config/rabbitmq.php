<?php

return [
    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
    'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),
    'exchange' => env('RABBITMQ_EXCHANGE', 'file.events'),
    'file_deleted_queue' => env('RABBITMQ_FILE_DELETED_QUEUE', 'email.file-deleted'),
    'file_deleted_routing_key' => env('RABBITMQ_FILE_DELETED_ROUTING_KEY', 'file.deleted'),
    'connection_timeout' => (float) env('RABBITMQ_CONNECTION_TIMEOUT', 3.0),
    'read_write_timeout' => (float) env('RABBITMQ_READ_WRITE_TIMEOUT', 65.0),
    'publish_timeout' => (float) env('RABBITMQ_PUBLISH_TIMEOUT', 5.0),
    'heartbeat' => (int) env('RABBITMQ_HEARTBEAT', 30),
    'app_id' => env('RABBITMQ_APP_ID', env('APP_NAME', 'file-retention')),
];
