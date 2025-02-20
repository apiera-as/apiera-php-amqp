.PHONY: up down status test setup wait-rabbitmq

DOCKER_COMPOSE := $(shell command -v docker-compose 2>/dev/null || echo "docker compose")

all: setup test

up:
	$(DOCKER_COMPOSE) up -d --build

down:
	$(DOCKER_COMPOSE) down --remove-orphans

status:
	$(DOCKER_COMPOSE) ps

wait-rabbitmq:
	@echo "Waiting for RabbitMQ to be ready..."
	@timeout 30s bash -c 'until docker exec rabbitmq rabbitmq-diagnostics -q ping 2>/dev/null; do echo "Waiting..."; sleep 1; done' || \
		(echo "RabbitMQ failed to start within timeout" && $(DOCKER_COMPOSE) logs rabbitmq && exit 1)
	@echo "RabbitMQ is ready!"

setup: up wait-rabbitmq

test:
	@$(DOCKER_COMPOSE) exec -T php vendor/bin/phpunit --testsuite=Integration