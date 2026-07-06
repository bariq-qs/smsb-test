.PHONY: help up down build rebuild restart stop ps logs logs-api logs-queue logs-vue logs-mysql \
        migrate seed fresh test tinker artisan api-shell queue-shell vue-shell mysql-shell clean

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'

up: ## Build (if needed) and start the full stack in the background
	docker compose up -d --build

down: ## Stop and remove all containers (keeps data volumes)
	docker compose down

build: ## Rebuild the api/queue images
	docker compose build

rebuild: ## Force a clean rebuild without Docker layer cache
	docker compose build --no-cache

restart: ## Restart all containers
	docker compose restart

stop: ## Stop all containers without removing them
	docker compose stop

ps: ## Show status of all containers
	docker compose ps

logs: ## Tail logs from every service
	docker compose logs -f

logs-api: ## Tail Laravel API logs
	docker compose logs -f api

logs-queue: ## Tail queue worker logs
	docker compose logs -f queue

logs-vue: ## Tail Vue dev server logs
	docker compose logs -f vue

logs-mysql: ## Tail MySQL logs
	docker compose logs -f mysql

migrate: ## Run pending migrations
	docker compose exec api php artisan migrate --force

seed: ## Run database seeders (demo data)
	docker compose exec api php artisan db:seed --force

fresh: ## Drop all tables, re-migrate, and reseed demo data
	docker compose exec api php artisan migrate:fresh --seed --force

test: ## Run the PHPUnit/Pest test suite (isolated in-memory SQLite, does not touch demo data)
	docker compose exec api php artisan test

tinker: ## Open a Laravel Tinker REPL
	docker compose exec api php artisan tinker

artisan: ## Run an arbitrary artisan command, e.g. make artisan cmd="route:list"
	docker compose exec api php artisan $(cmd)

api-shell: ## Open a shell in the api container
	docker compose exec api sh

queue-shell: ## Open a shell in the queue container
	docker compose exec queue sh

vue-shell: ## Open a shell in the vue container
	docker compose exec vue sh

mysql-shell: ## Open a MySQL client shell against the app database
	docker compose exec mysql mysql -usmsb_user -psmsb_password smsb_coding_test

clean: ## Stop containers and delete all data volumes (mysql data, node_modules, uploaded files)
	docker compose down -v
