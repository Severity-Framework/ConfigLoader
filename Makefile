# The current user's ID
USER_ID=$(shell id -u)

# Builds image and runs the unit tests
.PHONY: test
test:
	# Running the tests
	@docker-compose -f docker/docker-compose.yml -f docker/docker-compose.test.yml up --build --abort-on-container-exit --renew-anon-volumes --remove-orphans

# Starts the development environment up
.PHONY: dev
dev:
	@if [ ! -d "vendor/" ]; then docker run --rm -v $(shell pwd):/app -u ${USER_ID}:${USER_ID} composer:latest install -o --no-suggest --no-interaction --ignore-platform-reqs --no-scripts; fi
	# Start up the env
	-@docker-compose -f docker/docker-compose.yml -f docker/docker-compose.dev.yml up --build --abort-on-container-exit --renew-anon-volumes --remove-orphans

# Enters to the container with 'bash'
.PHONY: enter
enter:
	$(eval ID := $(shell docker ps -q --filter "label=com.docker.compose.service=config-loader"))
	@if [ -z $(ID) ]; then echo >&2 "ERROR: The 'config-loader' container is missing" && exit 1; fi
	# Entering the container
	@docker exec -it $(ID) bash
