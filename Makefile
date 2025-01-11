
DOCKER_COMPOSE_FILE := docker-compose.dev.yml
ifneq ("$(wildcard .env)","")
	include .env
endif
.PHONY: up down destroy banner

banner:
	@echo "                        __  _____ _______  __  "
	@echo "       ____ ___  ____ _/ /_/ ___// ____/ |/ /  "
	@echo "      / __  __ \/ __  / __/\__ \/ /_   |   /   "
	@echo "     / / / / / / /_/ / /_ ___/ / __/  /   |    "
	@echo "    /_/ /_/ /_/\__,_/\__//____/_/    /_/|_|    "
	@echo ""
up: banner
	@docker-compose -f $(DOCKER_COMPOSE_FILE) up -d --build
	@echo ""
	@echo "     Frontend:       http://localhost:$(PORT)"
	@echo "     PHPMyAdmin:     http://localhost:$(PHPMYADMIN_PORT)"
	@echo "     Minio:          http://localhost:$(MINIO_PORT)"
	@echo ""


down:
	@echo ""
	@echo "     PROJECT SHUTDOWN     "
	@echo ""
	@docker-compose -f $(DOCKER_COMPOSE_FILE) down


destroy:
	@echo ""
	@echo "     PROJECT DESTRUCTION   "
	@echo ""
	@docker-compose -f $(DOCKER_COMPOSE_FILE) down -v
	@echo "All volumes have been removed."


