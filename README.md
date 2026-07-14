# File Retention

Laravel application for uploading PDF and DOCX files, retaining them for a limited period, and publishing deletion notifications through RabbitMQ.

## Stack

- PHP 8.5 FPM
- Laravel 13
- Nginx
- MySQL 8.4 LTS
- RabbitMQ 4 with the management UI
- Docker Compose and Make

## Requirements

- Docker with the Compose plugin
- GNU Make

PHP, Composer, MySQL, and RabbitMQ do not need to be installed on the host.

## Start the application

```bash
make install
```

The command creates `.env` from `.env.example` when needed, builds the PHP image, starts the services, installs Composer dependencies, generates the application key, and runs database migrations.

Open:

- Application: http://localhost:8080
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
make shell-php
make artisan about
make artisan migrate
make composer require vendor/package
make test
```

## Configuration

The Docker services use the application `.env`. Important defaults are:

```dotenv
APP_URL=http://localhost:8080
DB_HOST=mysql
RABBITMQ_HOST=rabbitmq
MAIL_TO_ADDRESS=developer@example.com
```

Change `MAIL_TO_ADDRESS` to the recipient that should be included in file-deletion messages.
