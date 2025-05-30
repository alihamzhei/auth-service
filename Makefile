# Auth Service Docker Management
# Usage: make [target]

.PHONY: help setup build start stop restart logs test clean

# Default target
.DEFAULT_GOAL := help

# Colors
BLUE := \033[36m
GREEN := \033[32m
RED := \033[31m
RESET := \033[0m

help: ## Show this help message
	@echo "$(BLUE)Auth Service Docker Commands$(RESET)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(BLUE)%-15s$(RESET) %s\n", $$1, $$2}'

# Development Commands
setup: ## Initial setup for development
	@echo "$(BLUE)Setting up Auth Service for development...$(RESET)"
	@./docker/scripts/setup.sh setup

build: ## Build Docker images
	@echo "$(BLUE)Building Docker images...$(RESET)"
	@docker-compose build

start: ## Start development environment
	@echo "$(GREEN)Starting development environment...$(RESET)"
	@docker-compose up -d
	@echo "$(GREEN)✅ Services started at http://localhost:8000$(RESET)"

stop: ## Stop all services
	@echo "$(RED)Stopping all services...$(RESET)"
	@docker-compose down

restart: ## Restart all services
	@echo "$(BLUE)Restarting services...$(RESET)"
	@docker-compose restart

# Development Tools
logs: ## View application logs (use SERVICE=name for specific service)
	@docker-compose logs -f $(or $(SERVICE),app)

shell: ## Access application shell
	@docker-compose exec app bash

tinker: ## Access Laravel Tinker
	@docker-compose exec app php artisan tinker

migrate: ## Run database migrations
	@echo "$(BLUE)Running database migrations...$(RESET)"
	@docker-compose exec app php artisan migrate

migrate-fresh: ## Fresh migration with seeders
	@echo "$(BLUE)Running fresh migrations with seeders...$(RESET)"
	@docker-compose exec app php artisan migrate:fresh --seed

seed: ## Run database seeders
	@echo "$(BLUE)Running database seeders...$(RESET)"
	@docker-compose exec app php artisan db:seed

# Testing
test: ## Run tests
	@echo "$(BLUE)Running tests...$(RESET)"
	@docker-compose exec app php artisan test

test-coverage: ## Run tests with coverage
	@echo "$(BLUE)Running tests with coverage...$(RESET)"
	@docker-compose exec app php artisan test --coverage

# Production Commands
prod-build: ## Build production images
	@echo "$(BLUE)Building production images...$(RESET)"
	@docker build --target production -t auth-service:latest .

prod-deploy: ## Deploy to production
	@echo "$(BLUE)Deploying to production...$(RESET)"
	@./docker/scripts/deploy-prod.sh deploy

prod-start: ## Start production environment
	@echo "$(GREEN)Starting production environment...$(RESET)"
	@docker-compose -f docker-compose.prod.yml up -d

prod-stop: ## Stop production environment
	@echo "$(RED)Stopping production environment...$(RESET)"
	@docker-compose -f docker-compose.prod.yml down

prod-logs: ## View production logs
	@docker-compose -f docker-compose.prod.yml logs -f $(or $(SERVICE),app)

# Monitoring (optional services)
monitoring-start: ## Start monitoring services (Prometheus, Grafana)
	@echo "$(GREEN)Starting monitoring services...$(RESET)"
	@docker-compose --profile monitoring up -d prometheus grafana
	@echo "$(GREEN)✅ Prometheus: http://localhost:9090$(RESET)"
	@echo "$(GREEN)✅ Grafana: http://localhost:3000 (admin/admin)$(RESET)"

monitoring-stop: ## Stop monitoring services
	@echo "$(RED)Stopping monitoring services...$(RESET)"
	@docker-compose --profile monitoring down

# Development Services
dev-services: ## Start additional development services (MailHog)
	@echo "$(GREEN)Starting development services...$(RESET)"
	@docker-compose --profile development up -d mailhog
	@echo "$(GREEN)✅ MailHog: http://localhost:8025$(RESET)"

# Maintenance
clean: ## Clean up Docker resources
	@echo "$(RED)Cleaning up Docker resources...$(RESET)"
	@docker-compose down -v --remove-orphans
	@docker system prune -f

clean-all: ## Clean up everything including images
	@echo "$(RED)Cleaning up all Docker resources...$(RESET)"
	@docker-compose down -v --remove-orphans
	@docker system prune -af

# Cache Management
cache-clear: ## Clear application cache
	@echo "$(BLUE)Clearing application cache...$(RESET)"
	@docker-compose exec app php artisan cache:clear
	@docker-compose exec app php artisan config:clear
	@docker-compose exec app php artisan route:clear
	@docker-compose exec app php artisan view:clear

cache-warm: ## Warm up application cache
	@echo "$(BLUE)Warming up application cache...$(RESET)"
	@docker-compose exec app php artisan config:cache
	@docker-compose exec app php artisan route:cache
	@docker-compose exec app php artisan view:cache

# Database Management
db-backup: ## Create database backup
	@echo "$(BLUE)Creating database backup...$(RESET)"
	@docker-compose exec postgres pg_dump -U postgres auth_service > backup-$(shell date +%Y%m%d-%H%M%S).sql
	@echo "$(GREEN)✅ Database backup created$(RESET)"

db-restore: ## Restore database from backup (specify BACKUP_FILE=filename)
	@echo "$(BLUE)Restoring database from backup...$(RESET)"
	@docker-compose exec -T postgres psql -U postgres -d auth_service < $(BACKUP_FILE)

# Health Checks
health: ## Check service health
	@echo "$(BLUE)Checking service health...$(RESET)"
	@curl -f http://localhost:8000/api/health || echo "$(RED)Health check failed$(RESET)"

status: ## Show service status
	@echo "$(BLUE)Service Status:$(RESET)"
	@docker-compose ps

# Security
security-scan: ## Run security scan on images
	@echo "$(BLUE)Running security scan...$(RESET)"
	@docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
		-v $(PWD):/root/.cache/ aquasec/trivy auth-service:latest

# Utility Commands
env-check: ## Check environment configuration
	@echo "$(BLUE)Checking environment configuration...$(RESET)"
	@docker-compose config

generate-ssl: ## Generate self-signed SSL certificates for development
	@echo "$(BLUE)Generating SSL certificates...$(RESET)"
	@mkdir -p docker/ssl
	@openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
		-keyout docker/ssl/key.pem \
		-out docker/ssl/cert.pem \
		-subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"
	@echo "$(GREEN)✅ SSL certificates generated$(RESET)"

# Quick shortcuts
up: start ## Alias for start
down: stop ## Alias for stop
ps: status ## Alias for status