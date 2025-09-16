.PHONY: help setup dev build test clean deploy destroy

# Default target
help:
	@echo "Telehealth AI Intake System - Makefile Commands"
	@echo ""
	@echo "Development Commands:"
	@echo "  setup     - Install all dependencies"
	@echo "  dev       - Start development environment"
	@echo "  build     - Build all services"
	@echo "  test      - Run all tests"
	@echo "  clean     - Clean up development environment"
	@echo ""
	@echo "Infrastructure Commands:"
	@echo "  plan      - Show Terraform plan"
	@echo "  deploy    - Deploy to AWS"
	@echo "  destroy   - Destroy AWS infrastructure"
	@echo ""
	@echo "Docker Commands:"
	@echo "  docker-build - Build all Docker images"
	@echo "  docker-up    - Start all services with Docker Compose"
	@echo "  docker-down  - Stop all Docker Compose services"

# Development Commands
setup:
	@echo "📦 Installing dependencies..."
	npm install
	cd frontend && npm install
	cd ml-service && pip install -r requirements.txt
	@echo "✅ Dependencies installed"

dev:
	@echo "🚀 Starting development environment..."
	docker-compose up -d
	@echo "✅ Development environment started"
	@echo "Frontend: http://localhost:3000"
	@echo "Backend: http://localhost:8000"
	@echo "ML Service: http://localhost:8001"

build:
	@echo "🔨 Building all services..."
	cd frontend && npm run build
	@echo "✅ All services built"

test:
	@echo "🧪 Running all tests..."
	cd frontend && npm run test
	cd ml-service && pytest
	@echo "✅ All tests completed"

clean:
	@echo "🧹 Cleaning up..."
	docker-compose down -v
	docker system prune -f
	@echo "✅ Cleanup completed"

# Infrastructure Commands
plan:
	@echo "📋 Showing Terraform plan..."
	cd infra/terraform && terraform plan

deploy:
	@echo "🚀 Deploying to AWS..."
	cd infra/terraform && terraform apply -auto-approve
	@echo "✅ Deployment completed"

destroy:
	@echo "💥 Destroying AWS infrastructure..."
	cd infra/terraform && terraform destroy -auto-approve
	@echo "✅ Infrastructure destroyed"

# Docker Commands
docker-build:
	@echo "🐳 Building Docker images..."
	docker-compose build
	@echo "✅ Docker images built"

docker-up:
	@echo "🐳 Starting Docker Compose services..."
	docker-compose up -d
	@echo "✅ Services started"

docker-down:
	@echo "🐳 Stopping Docker Compose services..."
	docker-compose down
	@echo "✅ Services stopped"

# Linting and formatting
lint:
	@echo "🔍 Running linters..."
	cd frontend && npm run lint
	cd ml-service && black --check . && isort --check-only . && flake8 .
	@echo "✅ Linting completed"

lint-fix:
	@echo "🔧 Fixing lint issues..."
	cd frontend && npm run lint:fix
	cd ml-service && black . && isort .
	@echo "✅ Lint fixes applied"

# Database commands
db-migrate:
	@echo "📊 Running database migrations..."
	docker-compose exec backend php artisan migrate
	@echo "✅ Migrations completed"

db-seed:
	@echo "🌱 Seeding database..."
	docker-compose exec backend php artisan db:seed
	@echo "✅ Database seeded"

db-fresh:
	@echo "🔄 Fresh database with seed data..."
	docker-compose exec backend php artisan migrate:fresh --seed
	@echo "✅ Fresh database ready"

# Monitoring and debugging
logs:
	@echo "📜 Showing service logs..."
	docker-compose logs -f

logs-backend:
	@echo "📜 Showing backend logs..."
	docker-compose logs -f backend

logs-ml:
	@echo "📜 Showing ML service logs..."
	docker-compose logs -f ml-service

logs-frontend:
	@echo "📜 Showing frontend logs..."
	docker-compose logs -f frontend

# Security scanning
security-scan:
	@echo "🔒 Running security scans..."
	docker run --rm -v $(PWD):/app -w /app aquasec/trivy fs .
	@echo "✅ Security scan completed"