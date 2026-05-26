#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -f backend/.env ]; then
  cp backend/.env.example backend/.env
fi

docker compose up -d --build
sleep 12
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force

echo "Ready: http://localhost:${APP_PORT:-8080}"
