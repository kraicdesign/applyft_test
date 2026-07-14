<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Application\File\Command\DeleteFile\DeleteFileHandler;
use App\Domain\File\Contract\FileContentStorage;
use App\Domain\File\Contract\FileDeletionEmailSender;
use App\Domain\File\Contract\FileDeletionNotificationPublisher;
use App\Domain\File\Contract\StoredFileIdGenerator;
use App\Domain\File\Contract\StoredFileRepository;
use App\Domain\Shared\Contract\Clock;
use App\Infrastructure\Identity\UuidStoredFileIdGenerator;
use App\Infrastructure\Mail\LaravelFileDeletionEmailSender;
use App\Infrastructure\Messaging\RabbitMq\Console\ConsumeFileDeletionNotificationsCommand;
use App\Infrastructure\Messaging\RabbitMq\FileDeletionNotificationSerializer;
use App\Infrastructure\Messaging\RabbitMq\RabbitMqConnectionFactory;
use App\Infrastructure\Messaging\RabbitMq\RabbitMqFileDeletionNotificationPublisher;
use App\Infrastructure\Messaging\RabbitMq\RabbitMqMessagePublisher;
use App\Infrastructure\Messaging\RabbitMq\RabbitMqTopology;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentStoredFileRepository;
use App\Infrastructure\Persistence\Eloquent\Seeder\DatabaseSeeder;
use App\Infrastructure\Storage\LaravelFileContentStorage;
use App\Infrastructure\Time\SystemClock;
use DateTimeZone;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\ServiceProvider;

final class InfrastructureServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Eloquent/Migration');

        if ($this->app->runningInConsole()) {
            $this->commands([ConsumeFileDeletionNotificationsCommand::class]);
        }
    }

    public function register(): void
    {
        if (! class_exists('Database\\Seeders\\DatabaseSeeder', false)) {
            class_alias(DatabaseSeeder::class, 'Database\\Seeders\\DatabaseSeeder');
        }

        $this->app->bind(StoredFileRepository::class, EloquentStoredFileRepository::class);
        $this->app->singleton(StoredFileIdGenerator::class, UuidStoredFileIdGenerator::class);
        $this->app->singleton(Clock::class, static function (Application $application): SystemClock {
            $timezone = (string) $application->make(ConfigRepository::class)->get('app.timezone', 'UTC');

            return new SystemClock(new DateTimeZone($timezone));
        });
        $this->app->bind(FileContentStorage::class, static function (Application $application): LaravelFileContentStorage {
            $config = $application->make(ConfigRepository::class);
            $disk = (string) $config->get('filesystems.default', 'local');

            return new LaravelFileContentStorage(
                $application->make(FilesystemManager::class)->disk($disk),
            );
        });
        $this->app->bind(FileDeletionEmailSender::class, LaravelFileDeletionEmailSender::class);

        $this->app->singleton(RabbitMqConnectionFactory::class, static function (Application $application): RabbitMqConnectionFactory {
            $config = $application->make(ConfigRepository::class);

            return new RabbitMqConnectionFactory(
                host: (string) $config->get('rabbitmq.host'),
                port: (int) $config->get('rabbitmq.port'),
                user: (string) $config->get('rabbitmq.user'),
                password: (string) $config->get('rabbitmq.password'),
                vhost: (string) $config->get('rabbitmq.vhost'),
                connectionTimeout: (float) $config->get('rabbitmq.connection_timeout'),
                readWriteTimeout: (float) $config->get('rabbitmq.read_write_timeout'),
                heartbeat: (int) $config->get('rabbitmq.heartbeat'),
            );
        });

        $this->app->singleton(RabbitMqTopology::class, static function (Application $application): RabbitMqTopology {
            $config = $application->make(ConfigRepository::class);

            return new RabbitMqTopology(
                exchange: (string) $config->get('rabbitmq.exchange'),
                fileDeletedQueue: (string) $config->get('rabbitmq.file_deleted_queue'),
                fileDeletedRoutingKey: (string) $config->get('rabbitmq.file_deleted_routing_key'),
            );
        });

        $this->app->singleton(RabbitMqMessagePublisher::class, static function (Application $application): RabbitMqMessagePublisher {
            $config = $application->make(ConfigRepository::class);

            return new RabbitMqMessagePublisher(
                connectionFactory: $application->make(RabbitMqConnectionFactory::class),
                topology: $application->make(RabbitMqTopology::class),
                publishTimeout: (float) $config->get('rabbitmq.publish_timeout'),
            );
        });

        $this->app->singleton(
            FileDeletionNotificationPublisher::class,
            static function (Application $application): RabbitMqFileDeletionNotificationPublisher {
                $config = $application->make(ConfigRepository::class);

                return new RabbitMqFileDeletionNotificationPublisher(
                    messagePublisher: $application->make(RabbitMqMessagePublisher::class),
                    topology: $application->make(RabbitMqTopology::class),
                    serializer: $application->make(FileDeletionNotificationSerializer::class),
                    appId: (string) $config->get('rabbitmq.app_id'),
                );
            },
        );

        $this->app->bind(DeleteFileHandler::class, static function (Application $application): DeleteFileHandler {
            $config = $application->make(ConfigRepository::class);

            return new DeleteFileHandler(
                fileRepository: $application->make(StoredFileRepository::class),
                contentStorage: $application->make(FileContentStorage::class),
                notificationPublisher: $application->make(FileDeletionNotificationPublisher::class),
                clock: $application->make(Clock::class),
                notificationRecipient: (string) $config->get('mail.to.address'),
            );
        });
    }
}
