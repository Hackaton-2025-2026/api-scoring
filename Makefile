.PHONY: build start stop restart fixtures logs shell

build:
	docker-compose build --no-cache

start:
	docker-compose up -d

stop:
	docker-compose down

restart: stop build start

fixtures:
	docker exec -it web php bin/console doctrine:schema:update --force --no-interaction
	docker exec -it web php bin/console doctrine:fixtures:load --no-interaction

logs:
	docker exec -it web cat /var/log/cron.log

shell:
	docker exec -it web bash
