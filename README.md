# File Retention

Laravel application for uploading PDF and DOCX files, retaining them for a limited period, and publishing deletion notifications through RabbitMQ.

## Architecture

Business code uses four top-level layers under `src/`:

- `Application`: commands, queries, handlers, and use-case orchestration.
- `Domain`: interfaces, entities, value objects, and business rules.
- `Presentation`: HTTP, Artisan CLI, and Laravel scheduling adapters.
- `Infrastructure`: persistence, filesystem, RabbitMQ, and Laravel bindings.

See [docs/architecture.md](docs/architecture.md) for the dependency rules and directory conventions. Architecture tests validate namespace placement and prevent forbidden references between layers.
The default Laravel `app/`, `routes/`, and `database/` source roots are not used. Laravel's application path is mapped to `src/` so framework tooling remains compatible with the DDD source root. Bootstrap is the composition root; route files live in Presentation, while Eloquent models, migrations, factories, and seeders live in Infrastructure.
File-deletion notification is modeled inside the File domain.

## Stack

- PHP 8.5 FPM
- Laravel 13
- Nginx
- MySQL 8.4 LTS
- RabbitMQ 4 with the management UI
- Bootstrap 5 and jQuery 4, bundled with Vite
- php-amqplib 3.7
- Docker Compose and Make

## Requirements

- Docker with the Compose plugin
- GNU Make

PHP, Composer, MySQL, and RabbitMQ do not need to be installed on the host.

## Start the application

```bash
make install
```

The command creates `.env` from `.env.example` when needed, builds the PHP image, starts the services, installs Composer and frontend dependencies, generates the application key, runs database migrations, and builds the Vite assets. Node runs in a disposable Docker tools container and is not required on the host.

Open:

- Application: http://localhost:8080
- Upload page: http://localhost:8080/upload
- File management: http://localhost:8080/files
- RabbitMQ management: http://localhost:15672

The default RabbitMQ login is `laravel` / `laravel`. Local credentials and forwarded ports can be changed in `.env`.
`HOST_UID` and `HOST_GID` default to `1000` so files created in the PHP container remain editable by the host user.

## Common commands

```bash
make start
make stop
make down
make destroy                 # also removes database and RabbitMQ volumes
make ps
make logs
make logs php
make logs scheduler
make shell-php
make artisan about
make artisan migrate
make composer require vendor/package
make frontend-build
make test
```

## Automatic expiry

The `scheduler` Docker service runs Laravel's standard `schedule:work` process. `Presentation/Scheduling/FileRetentionSchedule` invokes the `DeleteExpiredFiles` Application handler every minute with overlap protection. Files are therefore removed within approximately one minute after their 24-hour retention period ends, and the existing deletion workflow publishes the RabbitMQ notification.

Inspect the registered task with:

```bash
make artisan schedule:list
```

## Web interface

The home route redirects to `/upload`. This page provides a responsive drag-and-drop PDF/DOCX uploader with client-side feedback and a real upload progress bar. jQuery submits the file asynchronously, while Presentation and Domain validation both enforce the 10 MB and file-type restrictions.

The `/files` management page lists stored-file metadata and expiry times. Files can be replaced or manually deleted without leaving the page. Both operations use Bootstrap modals and jQuery AJAX; server validation failures are shown next to the affected action.

## Configuration

The Docker services use the application `.env`. Important defaults are:

```dotenv
APP_URL=http://localhost:8080
DB_HOST=mysql
RABBITMQ_HOST=rabbitmq
RABBITMQ_EXCHANGE=file.events
RABBITMQ_FILE_DELETED_QUEUE=email.file-deleted
RABBITMQ_FILE_DELETED_ROUTING_KEY=file.deleted
RABBITMQ_PUBLISH_TIMEOUT=5
MAIL_MAILER=log
MAIL_LOG_CHANNEL=single
MAIL_TO_ADDRESS=developer@example.com
```

Change `MAIL_TO_ADDRESS` to the recipient that should be included in file-deletion messages.
Laravel sends mail through its built-in log transport in the local environment. Email contents are written to `storage/logs/laravel.log`; no SMTP server or external delivery is used.
File deletions publish a persistent `file.deleted.v1` JSON event to the durable `file.events` topic exchange and `email.file-deleted` queue. The publisher uses mandatory routing and RabbitMQ publisher confirms; a publishing failure is reported to the caller.
The `rabbitmq-consumer` Docker service receives these events, invokes the transport-neutral Application consumer, and sends the notification with Laravel Mail. With the default `log` mailer, rendered emails are visible in `storage/logs/laravel.log`. Messages are acknowledged only after Mail completes; malformed messages are rejected, while processing failures are requeued.

Uploaded file bytes are stored on Laravel's private `local` disk (`storage/app/private/files` by default). MySQL stores each file's UUID, original name, storage path, numeric domain file-type code, size, non-null upload time, and 24-hour expiry time. MIME mappings stay in the Domain `FileType` enum. Indexed expiry and upload columns support cleanup and listing queries.
