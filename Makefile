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