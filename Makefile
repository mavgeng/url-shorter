init: build up composer-install migrate test-setup ## Build containers, start, install dependencies and run migrations

build: ## Build containers
	docker compose build --no-cache

up: ## Start containers in detached mode
	docker compose up -d

down: ## Stop and remove containers
	docker compose down

logs: ## Show logs in real time
	docker compose logs -f

restart: down up ## Restart containers

php: ## Open bash in php container
	docker compose exec php bash

composer-install: ## Install composer dependencies
	docker compose --profile tools run --rm composer install --no-progress

composer-require: ## Add a package: make composer-require package=vendor/name
	docker compose --profile tools run --rm composer require $(package)

composer-update: ## Update composer dependencies
	docker compose --profile tools run --rm composer update --no-progress

migrate: ## Run database migrations
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

test:
	docker compose exec php bin/phpunit --testdox

test-setup:
	docker compose exec php bin/console doctrine:schema:create --env=test
