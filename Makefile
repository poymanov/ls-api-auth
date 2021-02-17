.DEFAULT_GOAL := restart

init: docker-down-clear \
	  docker-pull docker-build docker-up \
	  backend-init
up: docker-up
down: docker-down
restart: down up

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down --remove-orphans

docker-down-clear:
	docker-compose down -v --remove-orphans

docker-pull:
	docker-compose pull

docker-build:
	docker-compose build

wait-db:
	docker-compose run --rm php-cli wait-for-it db:3306 -t 30

backend-permissions:
	docker run --rm -v ${PWD}:/app -w /app alpine chmod 777 -R storage bootstrap

backend-composer-install:
	docker-compose run --rm php-cli composer install

backend-init: backend-permissions backend-composer-install backend-copy-env backend-generate-key wait-db backend-migrations

backend-copy-env:
	cp .env.example .env

backend-test:
	docker-compose run --rm php-cli php artisan test

backend-shell:
	docker-compose run --rm php-cli bash

backend-migrations:
	docker-compose run --rm php-cli php artisan migrate

backend-seed:
	docker-compose run --rm php-cli php artisan migrate:fresh --seed

backend-generate-key:
	docker-compose run --rm php-cli php artisan key:generate