# Project instructions

## Architecture

All business code must use the top-level layered structure under `src/`:

- `Application`: commands, queries, handlers, and application DTOs.
- `Domain`: entities, value objects, domain services, exceptions, and contracts/interfaces.
- `Presentation`: inbound HTTP, CLI, and scheduling adapters.
- `Infrastructure`: outbound adapters, persistence, storage, RabbitMQ, and Laravel wiring.

Do not introduce module-first or bounded-context-first top-level directories. Business areas such as `File` are nested inside the appropriate layer.

## Dependency rules

- Domain depends only on PHP and must not reference Application, Presentation, Infrastructure, Laravel, or Illuminate.
- Application may reference Domain only. It must not reference Presentation, Infrastructure, Laravel, or Illuminate.
- Presentation may reference Application and its delivery framework. It must not reference Domain or Infrastructure directly.
- Infrastructure may reference Application and Domain. It must not reference Presentation.
- `bootstrap/app.php` and `bootstrap/providers.php` are the composition root. Do not recreate Laravel's default `app/`, `routes/`, or `database/` source trees.
- Interfaces used by business workflows belong in Domain `Contract` directories. Infrastructure provides their implementations.
- Eloquent models belong in `Infrastructure/Persistence/Eloquent/Model`; they are not Domain entities.
- Eloquent migrations, factories, and seeders belong in the matching directories under `Infrastructure/Persistence/Eloquent`.
- HTTP and console route files belong under `Presentation/Http/Routes` and `Presentation/Cli/Routes`; bootstrap loads them directly.
- Laravel schedule registrars belong under `Presentation/Scheduling`; they invoke Application handlers and contain no retention business logic.
- Controllers and Artisan commands construct Application commands/queries and invoke handlers; they do not call repositories or Eloquent directly.
- Application handlers orchestrate Domain contracts and never use facades, HTTP request objects, Eloquent models, or vendor messaging clients.
- File size (maximum 10 MB), PDF/DOCX type, extension/type consistency, and 24-hour retention are authoritative Domain rules. Presentation should repeat relevant validation for user feedback without replacing Domain validation.
- File-deletion notification belongs to `Domain/File`, including its message, deletion reason, and publisher contract. RabbitMQ implementations belong to Infrastructure. Do not create a separate Notification domain unless notifications gain an independent business lifecycle and rules.
- Application message consumers process transport-neutral Domain messages and must not reference AMQP concepts. RabbitMQ receiver loops, acknowledgements, deserialization, and operational worker commands belong to Infrastructure; this is an explicit exception to the general placement of user-facing Artisan commands in Presentation.

## Required validation

For every code or architecture change:

1. Run `vendor/bin/pint`.
2. Run `make test` (or `php artisan test` in the PHP container).
3. Keep `tests/Architecture/LayerDependenciesTest.php` passing.
4. Update `docs/architecture.md` and `README.md` when layer responsibilities or the directory layout change.
