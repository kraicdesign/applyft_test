PHP_SERVICE ?= php

DC := docker compose --env-file ./.env -f ./docker-compose.yml

ARGS :=
LOGS_ARGS :=

ifneq ($(filter artisan,$(MAKECMDGOALS)),)
ARGS := $(filter-out artisan,$(MAKECMDGOALS))
override MAKECMDGOALS := artisan
endif

ifneq ($(filter composer,$(MAKECMDGOALS)),)
ARGS := $(filter-out composer,$(MAKECMDGOALS))
override MAKECMDGOALS := composer
endif

ifneq ($(filter logs,$(MAKECMDGOALS)),)
LOGS_ARGS := $(filter-out logs,$(MAKECMDGOALS))
override MAKECMDGOALS := logs
endif

.PHONY: install env build start stop down destroy restart ps logs shell-php shell-nginx artisan composer migrate test wait-mysql

install: env build start composer-install key-generate migrate

env:
	@if [ ! -f .env ]; then cp .env.example .env; fi

build:
	$(DC) build

start:
	$(DC) up -d
	$(MAKE) wait-mysql

stop:
	$(DC) stop

down:
	$(DC) down

destroy:
	$(DC) down -v

restart:
	$(DC) down
	$(DC) up -d
	$(MAKE) wait-mysql

ps:
	$(DC) ps

logs:
	$(DC) logs -f --tail=200 $(LOGS_ARGS)

shell-php:
	$(DC) exec $(PHP_SERVICE) sh

shell-nginx:
	$(DC) exec nginx sh

artisan:
	@$(DC) exec $(PHP_SERVICE) php artisan $(ARGS)

composer:
	@$(DC) exec $(PHP_SERVICE) composer $(ARGS)

composer-install:
	$(DC) exec $(PHP_SERVICE) composer install

key-generate:
	@$(DC) exec $(PHP_SERVICE) sh -c 'if grep -q "^APP_KEY=$$" .env; then php artisan key:generate; else echo "Application key already exists."; fi'

migrate:
	$(DC) exec $(PHP_SERVICE) php artisan migrate --force

test:
	$(DC) exec -e APP_ENV=testing $(PHP_SERVICE) php artisan test

wait-mysql:
	@echo "Waiting for MySQL to be ready..."
	@for i in $$(seq 1 60); do \
		if $(DC) exec -T mysql mysqladmin ping -h 127.0.0.1 -u"$${MYSQL_USER}" -p"$${MYSQL_PASSWORD}" --silent >/dev/null 2>&1; then \
			echo "MySQL is ready."; \
			exit 0; \
		fi; \
		sleep 1; \
	done; \
	echo "MySQL was not ready after 60 seconds." >&2; \
	exit 1

%:
	@:
