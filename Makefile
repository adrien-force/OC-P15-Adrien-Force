.PHONY: docker phpstan rector rector-fix reset-db restore-db db update-schema lint migration migrate test

restore-db:
	@for f in docker/postgres/*.sql; do \
		echo "Importing $$f"; \
		docker-compose exec -T postgres psql -U postgres -d ina_zaoui < $$f; \
	done

reset-db:
	docker-compose exec -T postgres psql -U postgres -c "DROP DATABASE IF EXISTS ina_zaoui;"
	docker-compose exec -T postgres psql -U postgres -c "CREATE DATABASE ina_zaoui;"

update-schema:
	php bin/console doctrine:schema:update --force

db:
	@echo "Restoring database..."
	$(MAKE) reset-db
	$(MAKE) update-schema
	$(MAKE) restore-db

docker:
	@echo "Starting Docker containers..."
	docker-compose up -d --force-recreate


phpstan:
	vendor/bin/phpstan analyse src --memory-limit=1G

rector:
	vendor/bin/rector process src --dry-run

rector-fix:
	vendor/bin/rector process src

lint:
	 ./vendor/bin/php-cs-fixer fix src

migration:
	php bin/console make:migration

migrate:
	php bin/console doctrine:migrations:migrate

test:
	./bin/phpunit