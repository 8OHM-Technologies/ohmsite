-include .env
export GITHUB_ACCESS_TOKEN

# Define phony targets so Make doesn't look for actual files with these names
.PHONY: up down status logs publish

# Default tag for Docker images built and published locally
TAG ?= latest

# -----------------------------------------------------------------------------
# GLOBAL COMMANDS
# -----------------------------------------------------------------------------

pull:
	echo $(GITHUB_ACCESS_TOKEN) | docker login ghcr.io -u 8ohm-tiaanf --password-stdin
	docker pull ghcr.io/8ohm-technologies/ohmsite-app:latest
	docker pull ghcr.io/8ohm-technologies/ohmsite-web:latest

# Spin up all containers in the background
up:
	docker compose up -d --build
	docker compose build ohmsite-app ohmsite-web

# Tear down all containers, networks, and volumes
down:
	docker compose down

prod-up:
	docker compose -f docker-compose.prod.yml pull
	docker compose -f docker-compose.prod.yml up -d --force-recreate


# Tear down all containers, networks, and volumes
prod-down:
	docker compose -f docker-compose.prod.yml down

# Spin up all containers in the background no build
up-no-build:
	docker compose up -d

# Check the status of all running services
ps:
	docker compose ps

# Tail logs for all services
logs:
	docker compose logs -f

# Build, tag, and push all custom Docker images to GHCR from local machine
publish:
	./publish_images.sh $(TAG)

# -----------------------------------------------------------------------------
# DYNAMIC INDIVIDUAL SERVICE COMMANDS
# -----------------------------------------------------------------------------

# Catch-all target: Spin up any individual container by its service name
# Example: 'make web' or 'make redis'
%:
	@docker compose up -d --build $@

# Dynamic target: Stop any individual container by prefixing 'stop-'
# Example: 'make stop-web' or 'make stop-redis'
stop-%:
	@docker compose stop $*

# Catch-all target (PROD): Spin up any individual container by its service name
# Example: 'make prod-web' or 'make prod-redis'
prod-%:
	@docker compose up -d --build $@

# Dynamic target (PROD): Stop any individual container by prefixing 'prod-stop-'
# Example: 'make prod-stop-web' or 'make prod-stop-redis'
prod-stop-%:
	@docker compose stop $*