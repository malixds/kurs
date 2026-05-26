.PHONY: up down build restart logs shell migrate seed fresh test install setup

up:
	docker compose up -d --build

down:
	docker compose down

build:
	docker compose build --no-cache

restart:
	docker compose restart

logs:
	docker compose logs -f

shell:
	docker compose exec app sh

migrate:
	docker compose exec app php artisan migrate --force

seed:
	docker compose exec app php artisan db:seed --force

fresh:
	docker compose exec app php artisan migrate:fresh --seed --force

test:
	docker compose exec app php artisan test

install:
	docker compose exec app composer install

setup: up
	@echo "Waiting for services..."
	@sleep 10
	docker compose exec app php artisan key:generate --force
	docker compose exec app php artisan migrate --force
	docker compose exec app php artisan db:seed --force
	@echo "Wellbeing platform ready at http://localhost:8080"
