.PHONY: build up down shell composer install test test-unit test-acceptance cs-fix analyze db-reset db-recreate

# Docker commands
build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

db-reset:
	docker compose up -d mysql
	docker compose run --rm app php bin/console app:db:recreate

db-recreate:
	docker compose run --rm app php bin/console app:db:recreate

shell:
	docker compose run --rm app sh

# Composer commands
composer:
	docker compose run --rm app composer $(filter-out $@,$(MAKECMDGOALS))

install:
	docker compose run --rm app composer install

# Application commands
calculate:
	docker compose run --rm app php bin/console app:calculate-shipping $(filter-out $@,$(MAKECMDGOALS))

# Test commands
test:
	docker compose run --rm app vendor/bin/phpunit

test-unit:
	docker compose run --rm app vendor/bin/phpunit --testsuite=Unit

test-acceptance:
	docker compose run --rm app vendor/bin/phpunit --testsuite=Acceptance

# Code quality
cs-fix:
	docker compose run --rm app vendor/bin/php-cs-fixer fix

analyze:
	docker compose run --rm app vendor/bin/phpstan analyse

%:
	@:


