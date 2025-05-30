#!/bin/bash

# Docker Setup Script for Auth Service
# This script helps set up the auth service with Docker

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is installed
check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    print_success "Docker and Docker Compose are installed"
}

# Create environment file
setup_env() {
    if [ ! -f .env ]; then
        print_status "Creating .env file from .env.docker template..."
        cp .env.docker .env
        
        print_warning "Please update the following in your .env file:"
        print_warning "- APP_KEY (run: php artisan key:generate)"
        print_warning "- JWT_SECRET (run: php artisan jwt:secret)"
        print_warning "- Database credentials if needed"
    else
        print_status ".env file already exists"
    fi
}

# Generate application key
generate_keys() {
    print_status "Generating application keys..."
    
    # Start temporary container to generate keys
    docker-compose run --rm app php artisan key:generate --no-interaction
    docker-compose run --rm app php artisan jwt:secret --no-interaction
    
    print_success "Keys generated successfully"
}

# Build and start containers
start_containers() {
    print_status "Building and starting containers..."
    docker-compose up -d --build
    
    # Wait for database to be ready
    print_status "Waiting for database to be ready..."
    sleep 30
    
    print_success "Containers started successfully"
}

# Run database migrations
run_migrations() {
    print_status "Running database migrations..."
    docker-compose exec app php artisan migrate --force
    
    print_status "Running database seeders..."
    docker-compose exec app php artisan db:seed --force
    
    print_success "Database setup completed"
}

# Set up storage permissions
setup_storage() {
    print_status "Setting up storage permissions..."
    docker-compose exec app chmod -R 775 storage bootstrap/cache
    docker-compose exec app php artisan storage:link
    
    print_success "Storage permissions set up"
}

# Run tests
run_tests() {
    print_status "Running tests..."
    docker-compose exec app php artisan test
    
    print_success "Tests completed"
}

# Show service URLs
show_urls() {
    print_success "🎉 Auth Service is ready!"
    echo ""
    echo "📋 Service URLs:"
    echo "   🌐 Application:    http://localhost:8000"
    echo "   🏥 Health Check:   http://localhost:8000/api/health"
    echo "   📊 Metrics:        http://localhost:8000/api/metrics"
    echo "   🗄️  Database:       localhost:5432"
    echo "   🗂️  Redis:          localhost:6379"
    echo "   📧 MailHog:        http://localhost:8025 (with development profile)"
    echo "   📈 Prometheus:     http://localhost:9090 (with monitoring profile)"
    echo "   📊 Grafana:        http://localhost:3000 (with monitoring profile)"
    echo ""
    echo "🔧 Useful commands:"
    echo "   docker-compose logs -f app          # View application logs"
    echo "   docker-compose exec app php artisan tinker  # Access Laravel console"
    echo "   docker-compose down                 # Stop all services"
    echo "   docker-compose down -v             # Stop and remove volumes"
}

# Main setup function
main() {
    print_status "🚀 Setting up Auth Service with Docker..."
    echo ""
    
    check_docker
    setup_env
    start_containers
    generate_keys
    run_migrations
    setup_storage
    
    # Ask if user wants to run tests
    read -p "Do you want to run tests? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        run_tests
    fi
    
    show_urls
}

# Parse command line arguments
case "${1:-setup}" in
    "setup")
        main
        ;;
    "start")
        print_status "Starting containers..."
        docker-compose up -d
        show_urls
        ;;
    "stop")
        print_status "Stopping containers..."
        docker-compose down
        print_success "Containers stopped"
        ;;
    "restart")
        print_status "Restarting containers..."
        docker-compose restart
        show_urls
        ;;
    "logs")
        docker-compose logs -f "${2:-app}"
        ;;
    "test")
        print_status "Running tests..."
        docker-compose exec app php artisan test
        ;;
    "migrate")
        print_status "Running migrations..."
        docker-compose exec app php artisan migrate
        ;;
    "fresh")
        print_warning "This will destroy all data. Are you sure? (y/n)"
        read -p "" -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            docker-compose down -v
            docker-compose up -d --build
            sleep 30
            docker-compose exec app php artisan migrate:fresh --seed --force
            print_success "Fresh setup completed"
        fi
        ;;
    "help"|"-h"|"--help")
        echo "Auth Service Docker Setup Script"
        echo ""
        echo "Usage: $0 [command]"
        echo ""
        echo "Commands:"
        echo "  setup     Full setup (default)"
        echo "  start     Start containers"
        echo "  stop      Stop containers"
        echo "  restart   Restart containers"
        echo "  logs      View logs (optional service name)"
        echo "  test      Run tests"
        echo "  migrate   Run migrations"
        echo "  fresh     Fresh setup (destroys data)"
        echo "  help      Show this help"
        ;;
    *)
        print_error "Unknown command: $1"
        print_status "Run '$0 help' for available commands"
        exit 1
        ;;
esac