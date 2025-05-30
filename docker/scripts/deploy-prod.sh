#!/bin/bash

# Production Deployment Script for Auth Service
# This script helps deploy the auth service to production

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
IMAGE_NAME="auth-service"
REGISTRY_URL=${REGISTRY_URL:-"your-registry.com"}
VERSION=${VERSION:-$(git rev-parse --short HEAD)}

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

# Check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check if Docker is installed
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed"
        exit 1
    fi
    
    # Check if we're in the right directory
    if [ ! -f "docker-compose.prod.yml" ]; then
        print_error "docker-compose.prod.yml not found. Are you in the right directory?"
        exit 1
    fi
    
    # Check if .env.production exists
    if [ ! -f ".env.production" ]; then
        print_error ".env.production file not found"
        exit 1
    fi
    
    print_success "Prerequisites check passed"
}

# Validate environment variables
validate_env() {
    print_status "Validating environment variables..."
    
    source .env.production
    
    # Check required variables
    required_vars=(
        "APP_KEY"
        "JWT_SECRET"
        "DB_PASSWORD"
        "DB_DATABASE"
        "DB_USERNAME"
    )
    
    for var in "${required_vars[@]}"; do
        if [ -z "${!var}" ]; then
            print_error "Required environment variable $var is not set"
            exit 1
        fi
    done
    
    print_success "Environment validation passed"
}

# Build production image
build_image() {
    print_status "Building production image..."
    
    # Build with production target
    docker build \
        --target production \
        --tag $IMAGE_NAME:$VERSION \
        --tag $IMAGE_NAME:latest \
        .
    
    print_success "Image built: $IMAGE_NAME:$VERSION"
}

# Push to registry (if registry is configured)
push_image() {
    if [ "$REGISTRY_URL" != "your-registry.com" ]; then
        print_status "Pushing image to registry..."
        
        # Tag for registry
        docker tag $IMAGE_NAME:$VERSION $REGISTRY_URL/$IMAGE_NAME:$VERSION
        docker tag $IMAGE_NAME:latest $REGISTRY_URL/$IMAGE_NAME:latest
        
        # Push to registry
        docker push $REGISTRY_URL/$IMAGE_NAME:$VERSION
        docker push $REGISTRY_URL/$IMAGE_NAME:latest
        
        print_success "Image pushed to registry"
    else
        print_warning "Registry URL not configured, skipping push"
    fi
}

# Run pre-deployment tests
run_tests() {
    print_status "Running tests..."
    
    # Create temporary test environment
    docker-compose -f docker-compose.yml run --rm app php artisan test
    
    print_success "Tests passed"
}

# Deploy to production
deploy() {
    print_status "Deploying to production..."
    
    # Copy production environment
    cp .env.production .env
    
    # Pull latest images (if using registry)
    if [ "$REGISTRY_URL" != "your-registry.com" ]; then
        docker-compose -f docker-compose.prod.yml pull
    fi
    
    # Start services
    docker-compose -f docker-compose.prod.yml up -d --build
    
    # Wait for services to be ready
    print_status "Waiting for services to be ready..."
    sleep 30
    
    # Run migrations
    print_status "Running database migrations..."
    docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
    
    # Clear and cache configurations
    print_status "Optimizing application..."
    docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache
    
    print_success "Deployment completed"
}

# Health check
health_check() {
    print_status "Performing health check..."
    
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f -s http://localhost/api/health > /dev/null; then
            print_success "Health check passed"
            return 0
        fi
        
        print_status "Health check attempt $attempt/$max_attempts failed, retrying..."
        sleep 10
        ((attempt++))
    done
    
    print_error "Health check failed after $max_attempts attempts"
    return 1
}

# Rollback function
rollback() {
    print_warning "Rolling back deployment..."
    
    # Stop current containers
    docker-compose -f docker-compose.prod.yml down
    
    # Start previous version (implement your rollback logic here)
    print_error "Rollback logic not implemented. Please manually restore previous version."
}

# Show deployment status
show_status() {
    print_status "Deployment Status:"
    echo ""
    
    # Show running containers
    docker-compose -f docker-compose.prod.yml ps
    
    echo ""
    print_status "Service URLs:"
    echo "   🌐 Application:    https://your-domain.com"
    echo "   🏥 Health Check:   https://your-domain.com/api/health"
    echo "   📊 Metrics:        https://your-domain.com/api/metrics"
}

# Backup database
backup_database() {
    print_status "Creating database backup..."
    
    local backup_file="backup-$(date +%Y%m%d-%H%M%S).sql"
    
    docker-compose -f docker-compose.prod.yml exec -T postgres pg_dump \
        -U "$DB_USERNAME" \
        -d "$DB_DATABASE" > "$backup_file"
    
    print_success "Database backup created: $backup_file"
}

# Main deployment function
main() {
    print_status "🚀 Starting production deployment..."
    echo ""
    
    check_prerequisites
    validate_env
    
    # Ask for confirmation
    print_warning "This will deploy to PRODUCTION. Are you sure? (y/n)"
    read -p "" -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_status "Deployment cancelled"
        exit 0
    fi
    
    # Create backup
    backup_database
    
    # Run tests
    run_tests
    
    # Build and deploy
    build_image
    push_image
    deploy
    
    # Verify deployment
    if health_check; then
        show_status
        print_success "🎉 Production deployment completed successfully!"
    else
        print_error "Deployment verification failed"
        print_warning "Consider rolling back if issues persist"
        exit 1
    fi
}

# Parse command line arguments
case "${1:-deploy}" in
    "deploy")
        main
        ;;
    "build")
        check_prerequisites
        build_image
        push_image
        ;;
    "status")
        show_status
        ;;
    "health")
        health_check
        ;;
    "backup")
        backup_database
        ;;
    "rollback")
        rollback
        ;;
    "logs")
        docker-compose -f docker-compose.prod.yml logs -f "${2:-app}"
        ;;
    "help"|"-h"|"--help")
        echo "Production Deployment Script for Auth Service"
        echo ""
        echo "Usage: $0 [command]"
        echo ""
        echo "Commands:"
        echo "  deploy    Full deployment (default)"
        echo "  build     Build and push images only"
        echo "  status    Show deployment status"
        echo "  health    Run health check"
        echo "  backup    Create database backup"
        echo "  rollback  Rollback deployment"
        echo "  logs      View logs (optional service name)"
        echo "  help      Show this help"
        echo ""
        echo "Environment Variables:"
        echo "  REGISTRY_URL    Docker registry URL"
        echo "  VERSION         Image version tag (default: git commit hash)"
        ;;
    *)
        print_error "Unknown command: $1"
        print_status "Run '$0 help' for available commands"
        exit 1
        ;;
esac