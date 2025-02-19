.PHONY: up down status test setup

DOCKER_COMPOSE := $(shell command -v docker-compose 2> /dev/null || echo "docker compose")

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

status:
	$(DOCKER_COMPOSE) ps

wait-rabbitmq:
	@echo "Waiting for RabbitMQ to be ready..."
	@timeout 30 bash -c 'until docker exec $$(docker ps -q -f name=rabbitmq) rabbitmq-diagnostics -q ping; do sleep 1; done'

setup: up wait-rabbitmq

test:
	vendor/bin/phpunit --testsuite=Integration