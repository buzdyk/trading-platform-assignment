.PHONY: up down build restart logs shell artisan migrate fresh seed test tinker composer npm queue help

# Colors
YELLOW := \033[1;33m
NC := \033[0m

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(YELLOW)%-15s$(NC) %s\n", $$1, $$2}'

# Docker
up: ## Start all containers
	BUILDKIT_PROGRESS=plain docker compose up -d --no-build

down: ## Stop all containers
	docker compose down

build: ## Build/rebuild containers
	docker compose build --no-cache

restart: ## Restart all containers
	docker compose restart

logs: ## View container logs (use: make logs s=app)
	docker compose logs -f $(s)

ps: ## List running containers
	docker compose ps

# Laravel
shell: ## Open shell in app container
	docker compose exec app sh

artisan: ## Run artisan command (use: make artisan c="migrate")
	docker compose exec app php artisan $(c)

migrate: ## Run migrations
	docker compose exec app php artisan migrate

fresh: ## Fresh migration with seeders
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Run seeders
	docker compose exec app php artisan db:seed

test: ## Run tests
	docker compose exec app php artisan test

tinker: ## Open tinker REPL
	docker compose exec app php artisan tinker

queue: ## Start queue worker
	docker compose exec app php artisan queue:work

# Composer
composer: ## Run composer command (use: make composer c="require package")
	docker compose exec app composer $(c)

install: ## Install composer dependencies
	docker compose exec app composer install

# Frontend
npm: ## Run npm command (use: make npm c="install")
	docker compose exec frontend npm $(c)

# Setup
setup: ## Initial project setup
	cp api/.env.example api/.env
	docker compose up -d
	docker compose exec app composer install
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate --seed
	@echo "$(YELLOW)Setup complete! Visit http://localhost$(NC)"

# Database
db: ## Open PostgreSQL CLI
	docker compose exec pgsql psql -U trading -d trading

db-dump: ## Dump database to file
	docker compose exec pgsql pg_dump -U trading trading > backup.sql

db-restore: ## Restore database from file
	docker compose exec -T pgsql psql -U trading trading < backup.sql

# Cache
cache-clear: ## Clear all caches
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

optimize: ## Optimize for production
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache

# Cleanup
prune: ## Remove unused Docker resources
	docker system prune -f
	docker volume prune -f
