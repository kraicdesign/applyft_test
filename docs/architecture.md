# Architecture

The project uses four top-level layers under `src/`.

```text
src/
├── Application/
│   └── File/
│       ├── Command/
│       ├── Consumer/
│       └── Query/
├── Domain/
│   └── File/
│       ├── Contract/
│       ├── Entity/
│       └── ValueObject/
├── Presentation/
│   ├── Cli/Routes/
│   ├── Http/Routes/
│   └── Scheduling/
└── Infrastructure/
    ├── Identity/
    ├── Messaging/RabbitMq/
    ├── Persistence/Eloquent/
    │   ├── Factory/
    │   ├── Migration/
    │   ├── Model/
    │   ├── Repository/
    │   └── Seeder/
    ├── Providers/
    ├── Storage/
    └── Time/
```

## Dependency direction

```text
Presentation ──> Application ──> Domain
                                      ^
                                      │
Infrastructure ───────────────────────┘
              └────────> Application
```

Dependencies always point inward:

- `Domain` depends on no other project layer and contains no Laravel code.
- `Application` depends only on `Domain` and PHP. It contains commands, queries, handlers, and application DTOs.
- `Presentation` depends on `Application` and delivery frameworks such as Laravel HTTP or Artisan. It must not use Domain or Infrastructure directly.
- `Infrastructure` may depend on Application and Domain. It implements Domain contracts and owns Eloquent, filesystem, RabbitMQ, and framework wiring. It must not depend on Presentation classes.
- `bootstrap/app.php` and `bootstrap/providers.php` are the composition root. They connect Laravel to Presentation route files and Infrastructure providers. The default Laravel `app/`, `routes/`, and `database/` source roots are intentionally not used.

## Layer responsibilities

### Application

Every write use case is represented by an immutable command and a handler in the same use-case directory:

```text
Application/File/Command/CreateFile/
├── CreateFileCommand.php
└── CreateFileHandler.php
```

Reads use the equivalent `Query/<UseCase>` layout. Handlers orchestrate Domain contracts; they do not use Eloquent, facades, HTTP requests, or RabbitMQ clients.

The file write use cases are `CreateFile`, `UpdateFile`, `DeleteFile`, and `DeleteExpiredFiles`. Create and update accept transport-neutral temporary-file metadata; their handlers construct Domain-valid `StoredFile` entities before writing content or metadata.

### Domain

Contains entities, value objects, domain services, exceptions, and interfaces. Repository and gateway interfaces are defined under `Contract`. Domain code must be deterministic and framework-independent.

File size, the PDF/DOCX cases in the `FileType` domain enum, matching MIME types and filename extensions, and the 24-hour retention period are Domain policies. Presentation may repeat size and format checks to provide immediate feedback, but Domain validation remains authoritative.

File-deletion notification is part of the File domain. Deletion is the business event that creates the notification, and notification currently has no independent lifecycle, preferences, templates, channels, or history. Therefore `FileDeletionNotification`, `DeletionReason`, and `FileDeletionNotificationPublisher` live under `Domain/File`; a separate `Domain/Notification` module must not be introduced for this workflow.

RabbitMQ remains an Infrastructure detail. Its publisher adapter implements `FileDeletionNotificationPublisher` and serializes the File domain message. A separate Notification domain should be considered only if notification delivery later becomes an independent business capability with its own rules and lifecycle.

Inbound delivery is split at the transport boundary. `RabbitMqFileDeletionNotificationReceiver` owns queue polling, JSON deserialization, acknowledgement, rejection, and requeue behavior. It passes a framework-neutral Domain notification to `Application/File/Consumer/FileDeletionNotificationConsumer`, which invokes the Domain email-sender port. The Laravel Infrastructure adapter sends through the configured mailer; locally this is the `log` transport.

The RabbitMQ operational worker command lives in Infrastructure because it directly controls an AMQP receiver loop. This is intentionally distinct from user-facing Artisan commands, which remain Presentation adapters.

The RabbitMQ adapter publishes a framework-neutral `file.deleted.v1` JSON event rather than a serialized Laravel job. It declares a durable topic exchange and durable email queue, publishes persistent messages with routing key `file.deleted`, requires the message to be routable, and waits for a publisher confirmation. `RabbitMqFileDeletionNotificationPublisher` creates the domain-specific envelope and delegates connection, mandatory-routing, and publisher-confirm mechanics to the reusable `RabbitMqMessagePublisher` service.

The default topology is:

```text
file.events (durable topic exchange)
    └── file.deleted
        └── email.file-deleted (durable queue)
```

### Presentation

Contains inbound adapters:

- `Http`: controllers, form requests, route definitions, and response mapping.
- `Cli`: Artisan commands and console output mapping.
- `Scheduling`: Laravel schedule registrars that trigger Application use cases.

Presentation parses input, constructs an Application command/query, invokes its handler, and maps the result. It contains no business rules.

`FileRetentionSchedule` runs the `DeleteExpiredFiles` Application use case every minute and prevents overlapping executions. The dedicated Docker scheduler service runs Laravel's foreground schedule worker. Expiry selection, deletion, and notification publishing remain in the Application and Domain layers rather than the schedule adapter.

### Infrastructure

Contains outbound adapters and configuration: Eloquent models, repositories, migrations, factories and seeders; Laravel filesystem storage; RabbitMQ publishers; clocks; and service providers. Eloquent models are persistence details and never Domain entities. Adapters implement Domain contracts and are bound in `InfrastructureServiceProvider`.

`EloquentStoredFileRepository` maps between the Domain `StoredFile` entity and the `stored_files` table. The table stores the UUID, original name, private storage path, numeric `FileType` code, byte size, non-null upload timestamp, and expiry timestamp. MIME strings remain behavior of the Domain enum rather than duplicated database values. The table has an expiry index for retention cleanup, an upload-time/ID index for stable listing, and a unique storage-path index. File bytes are handled independently through `LaravelFileContentStorage` on Laravel's configured filesystem disk.

`InfrastructureServiceProvider` registers the Infrastructure migration path. It also exposes the infrastructure `DatabaseSeeder` through Laravel's conventional default seeder name so `php artisan db:seed` continues to work.

## Automated enforcement

`tests/Architecture/LayerDependenciesTest.php` validates every PHP file below `src/`. It checks namespace-to-directory alignment and rejects forbidden layer/framework references. The test runs as part of `make test` and must remain green for every architecture change.
