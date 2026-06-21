.PHONY: help up down build rebuild shell artisan migrate fresh seed test test-up logs ps

## ─── Docker ───────────────────────────────────────────────────────────────────
help: ## Mostra questo messaggio
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Avvia lo stack in background
	docker compose up -d

down: ## Ferma e rimuove i container
	docker compose down

build: ## Builda le immagini
	docker compose build

rebuild: ## Force-rebuild senza cache
	docker compose build --no-cache

restart: ## Restart del container app
	docker compose restart app

## ─── App ──────────────────────────────────────────────────────────────────────
shell: ## Apri bash nel container app
	docker compose exec app bash

artisan: ## Esegui un comando artisan  (es: make artisan cmd="route:list")
	docker compose exec app php artisan $(cmd)

migrate: ## Esegui le migration
	docker compose exec app php artisan migrate

fresh: ## Drop + migrate + seed
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Esegui i seeder
	docker compose exec app php artisan db:seed

test-up: ## Avvia solo i container necessari per i test (db_test + redis)
	docker compose up -d db_test redis
	@echo "Attendo che db_test sia healthy..."
	@until docker compose exec -T db_test mysqladmin ping -h localhost -u root -p$${DB_ROOT_PASSWORD:-root_secret} --silent 2>/dev/null; do sleep 2; done
	@echo "db_test pronto."

test: test-up ## Esegui la test suite contro MySQL + Redis reale
	docker compose exec app php artisan test

composer: ## Esegui composer  (es: make composer cmd="require package/name")
	docker compose exec app composer $(cmd)

## ─── Utility ──────────────────────────────────────────────────────────────────
logs: ## Segui i log in real-time
	docker compose logs -f

ps: ## Stato dei container
	docker compose ps

key: ## Genera APP_KEY
	docker compose exec app php artisan key:generate
