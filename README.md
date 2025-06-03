# Authentication & Authorization Microservice

A robust, production-ready authentication and authorization microservice built with **Laravel 12** and **Clean Architecture** principles. This service provides secure JWT-based authentication, role-based access control, and comprehensive API endpoints for user management.

## 🏗️ Architecture

This microservice follows **Clean Architecture** principles with clear separation of concerns:

```
├── Domain/           # Business logic and entities
│   ├── Entities/     # Core business entities (User, Role, Permission)
│   ├── Interfaces/   # Repository and service interfaces  
│   └── UseCases/     # Business use cases and operations
├── Application/      # Application services and DTOs
│   ├── DTOs/         # Data Transfer Objects
│   └── Services/     # Application services
└── Infrastructure/   # External concerns (HTTP, database, etc.)
    ├── Http/         # Controllers, middleware, routes
    └── Repositories/ # Data persistence implementations
```

### Key Design Decisions

- **UUID-based User Identification**: Users are identified by UUIDs instead of auto-incrementing IDs for security
- **Domain-Driven Design**: Domain entities control their own business rules (e.g., UUID generation)
- **Dependency Injection**: All dependencies are injected through interfaces
- **File-based Token Storage**: Refresh tokens are persisted to the filesystem for reliability

## 🚀 Features

### Authentication
- ✅ **User Registration** with validation and password hashing
- ✅ **JWT-based Login** with access and refresh tokens
- ✅ **Token Refresh** mechanism for seamless user experience
- ✅ **Secure Logout** with token invalidation
- ✅ **Token Validation** endpoint for other services

### Authorization
- ✅ **Role-based Access Control** using Spatie Laravel Permission
- ✅ **Permission Management** with flexible role assignments
- ✅ **JWT Middleware** for route protection

### Security
- ✅ **Rate Limiting** on login attempts
- ✅ **Password Hashing** with bcrypt
- ✅ **UUID Exposure** instead of database IDs
- ✅ **JWT Token Security** with configurable expiration

### Observability
- ✅ **Health Check** endpoint for monitoring
- ✅ **Prometheus Metrics** for observability
- ✅ **Structured Logging** with Laravel Pail
- ✅ **API Documentation** with L5 Swagger

## 📋 Prerequisites

- **PHP 8.2+**
- **Composer 2.0+**
- **SQLite** (default) or **PostgreSQL**
- **Node.js & NPM** (for asset compilation)

## 🛠️ Installation

### 1. Clone and Setup

```bash
git clone <repository-url>
cd auth-service
cp .env.example .env
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Generate JWT Secret

```bash
php artisan jwt:secret
```

### 5. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed permissions and roles (optional)
php artisan db:seed
```

### 6. Storage Setup

```bash
# Create storage links
php artisan storage:link

# Set proper permissions
chmod -R 775 storage bootstrap/cache
```

## 🔧 Configuration

### Environment Variables

Key environment variables to configure:

```env
# Application
APP_NAME="Auth Service"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# JWT Configuration
JWT_SECRET=your-secret-key
JWT_TTL=60                    # Access token TTL in minutes
JWT_REFRESH_TTL=20160        # Refresh token TTL in minutes (2 weeks)

# Rate Limiting
RATE_LIMIT_LOGIN=5           # Max login attempts per minute

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### JWT Configuration

The service uses JWT for stateless authentication. Key configuration in `config/jwt.php`:

- **Algorithm**: HS256 (configurable)
- **Access Token TTL**: 1 hour (configurable)
- **Refresh Token TTL**: 2 weeks (configurable)
- **Blacklist**: Enabled for secure logout

## 🚦 Running the Service

### Development Mode

```bash
# Start development server
php artisan serve

# Or use the comprehensive dev script
composer run dev
```

The development script runs:
- **Laravel Server** on port 8000
- **Queue Worker** for background jobs
- **Laravel Pail** for real-time logs
- **Vite** for asset compilation

### Production Mode

```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start with proper web server (nginx + php-fpm)
```

## 📊 API Documentation

### Authentication Endpoints

| Method | Endpoint | Description | Authentication |
|--------|----------|-------------|----------------|
| POST | `/api/auth/register` | Register new user | None |
| POST | `/api/auth/login` | User login | None |
| POST | `/api/auth/refresh` | Refresh access token | Bearer Token |
| POST | `/api/auth/logout` | User logout | Bearer Token |
| POST | `/api/auth/validate` | Validate token | Bearer Token |

### System Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Health check |
| GET | `/api/metrics` | Prometheus metrics |

### Detailed API Reference

#### Register User

```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com", 
  "password": "password123"
}
```

**Response (201):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "email": "john@example.com"
}
```

#### Login

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "refresh_token": "a1b2c3d4e5f6...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

#### Validate Token

```http
POST /api/auth/validate
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "email": "john@example.com",
  "roles": ["admin", "user"]
}
```

#### Refresh Token

```http
POST /api/auth/refresh
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "refresh_token": "a1b2c3d4e5f6..."
}
```

**Response (200):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "refresh_token": "x1y2z3a4b5c6...",
  "token_type": "bearer", 
  "expires_in": 3600
}
```

#### Logout

```http
POST /api/auth/logout
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "refresh_token": "a1b2c3d4e5f6..."
}
```

**Response (200):**
```json
{
  "message": "Successfully logged out"
}
```

### Error Responses

All endpoints return consistent error responses:

```json
{
  "error": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

Common HTTP status codes:
- **401**: Unauthorized (invalid credentials/token)
- **422**: Validation Error
- **500**: Internal Server Error

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test tests/Feature/AuthControllerTest.php
php artisan test tests/Feature/AuthIntegrationTest.php

# Run with coverage
php artisan test --coverage
```

### Test Structure

- **Unit Tests**: Test individual components with mocks
- **Integration Tests**: Test complete workflows end-to-end
- **Feature Tests**: Test HTTP endpoints and responses

### Test Coverage

The service includes comprehensive test coverage:

- ✅ **27 Tests** covering all authentication flows
- ✅ **105 Assertions** ensuring robust validation
- ✅ **Mock-based Unit Tests** for isolated testing
- ✅ **Integration Tests** for complete user journeys

### Example Test Flow

```php
// Complete authentication flow test
public function test_complete_auth_flow()
{
    // 1. Register user
    $registerResponse = $this->postJson('/api/auth/register', [...]);
    
    // 2. Login
    $loginResponse = $this->postJson('/api/auth/login', [...]);
    
    // 3. Validate token
    $validateResponse = $this->postJson('/api/auth/validate', [], [
        'Authorization' => 'Bearer ' . $accessToken
    ]);
    
    // 4. Refresh token
    $refreshResponse = $this->postJson('/api/auth/refresh', [...]);
    
    // 5. Logout
    $logoutResponse = $this->postJson('/api/auth/logout', [...]);
}
```

## 📈 Monitoring & Observability

### Health Checks

The service provides health check endpoints for monitoring:

```http
GET /api/health
```

**Response:**
```json
{
  "status": "ok",
  "timestamp": "2025-05-30T23:30:00Z",
  "services": {
    "database": "ok",
    "redis": "ok"
  }
}
```

### Metrics

Prometheus metrics available at `/api/metrics`:

- **User Count**: Total registered users
- **Request Metrics**: Request rate, duration, errors
- **Auth Metrics**: Login attempts, token generation
- **System Metrics**: Memory usage, uptime

### Logging

Structured logging with Laravel Pail:

```bash
# Watch logs in real-time
php artisan pail

# Filter by level
php artisan pail --filter="level:error"
```

## 🐳 Docker Deployment

This project includes comprehensive Docker support with multi-stage builds, production optimizations, and complete orchestration setup.

### Quick Start with Docker

```bash
# Quick setup (recommended)
make setup

# Or use the setup script directly
./docker/scripts/setup.sh

# Or manual setup
cp .env.docker .env
docker-compose up -d --build
docker-compose exec app php artisan migrate --seed
```

### Docker Architecture

The Docker setup includes:

- **Multi-stage Dockerfile** (development/production targets)
- **PostgreSQL** database with optimized configuration
- **Redis** for caching and sessions
- **Nginx** reverse proxy with security headers
- **Queue Worker** for background jobs
- **Scheduler** for Laravel task scheduling
- **Health checks** and monitoring

### Available Services

| Service | Description | Port | URL |
|---------|-------------|------|-----|
| **app** | Laravel application | 9000 | - |
| **nginx** | Web server | 8000 | http://localhost:8000 |
| **postgres** | Database | 5432 | localhost:5432 |
| **redis** | Cache/Sessions | 6379 | localhost:6379 |
| **queue** | Queue worker | - | - |
| **scheduler** | Task scheduler | - | - |

### Make Commands

The project includes a comprehensive Makefile for easy management:

#### Development Commands
```bash
make setup           # Initial project setup
make start           # Start development environment
make stop            # Stop all services
make restart         # Restart services
make logs            # View application logs
make logs SERVICE=nginx  # View specific service logs
make shell           # Access application shell
make tinker          # Access Laravel Tinker
```

#### Database Commands
```bash
make migrate         # Run migrations
make migrate-fresh   # Fresh migrations with seeders
make seed            # Run seeders only
make db-backup       # Create database backup
make db-restore BACKUP_FILE=backup.sql  # Restore from backup
```

#### Testing Commands
```bash
make test            # Run tests
make test-coverage   # Run tests with coverage
```

#### Cache Management
```bash
make cache-clear     # Clear all caches
make cache-warm      # Warm up caches for production
```

#### Health Commands
```bash
make health            # Check service health
make status            # Show service status
```

#### Production Commands
```bash
make prod-build      # Build production images
make prod-deploy     # Deploy to production
make prod-start      # Start production environment
make prod-logs       # View production logs
```

#### Maintenance Commands
```bash
make clean           # Clean up Docker resources
make clean-all       # Clean everything including images
make security-scan   # Run security scan on images
```

### Development Environment

#### Starting Development Environment

```bash
# Option 1: Use Make (recommended)
make setup

# Option 2: Use setup script
./docker/scripts/setup.sh

# Option 3: Manual setup
cp .env.docker .env
docker-compose up -d --build
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
docker-compose exec app php artisan migrate --seed
```

#### Development Features

- **Hot reloading** with volume mounts
- **Detailed logging** with Laravel Pail
- **Development-optimized** PHP configuration
- **Automatic migrations** and seeding

#### Accessing Services

```bash
# Application shell
make shell
# or
docker-compose exec app bash

# Laravel Tinker
make tinker
# or
docker-compose exec app php artisan tinker

# View logs
make logs
# or
docker-compose logs -f app

# Database access
docker-compose exec postgres psql -U postgres -d auth_service
```

### Production Environment

#### Production Deployment

```bash
# Setup production environment file
cp .env.production .env
# Edit .env with production values

# Deploy using script (recommended)
./docker/scripts/deploy-prod.sh

# Or using Make
make prod-deploy

# Manual deployment
docker-compose -f docker-compose.prod.yml up -d --build
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

#### Production Features

- **Optimized images** with multi-stage builds
- **Resource limits** and health checks
- **SSL/TLS termination** with security headers
- **Rate limiting** and DDoS protection
- **Production caching** (OPcache, Redis)
- **Monitoring integration** (Prometheus metrics)
- **Automated backups** and rollback support

#### Environment Variables

**Development (.env.docker):**
```env
APP_ENV=local
DB_HOST=postgres
REDIS_HOST=redis
APP_PORT=8000
```

**Production (.env.production):**
```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=your-prod-db-host
REDIS_HOST=your-prod-redis-host
APP_URL=https://your-domain.com
```


### Health Checks & Monitoring

#### Built-in Health Checks

All services include health checks:

```bash
# Check service health
make health

# View service status
make status

# Monitor logs in real-time
make logs
```


#### Custom Metrics

The application exposes metrics at `/api/metrics`:

- User registration count
- Login attempts
- Token generation rate
- Response times
- Error rates

### Troubleshooting

#### Common Issues

1. **Port conflicts:**
   ```bash
   # Change ports in .env
   APP_PORT=8001
   DB_PORT=5433
   REDIS_PORT=6380
   ```

2. **Permission issues:**
   ```bash
   # Fix storage permissions
   docker-compose exec app chmod -R 775 storage bootstrap/cache
   ```

3. **Database connection issues:**
   ```bash
   # Check database status
   docker-compose exec postgres pg_isready
   
   # Restart database
   docker-compose restart postgres
   ```

4. **Memory issues:**
   ```bash
   # Increase Docker memory limit in Docker Desktop
   # Or optimize services
   docker-compose down
   docker system prune -f
   ```

#### Debug Commands

```bash
# View all logs
docker-compose logs

# Check environment
make env-check

# Container resource usage
docker stats

# Network connectivity
docker-compose exec app ping postgres
docker-compose exec app ping redis
```

#### Fresh Start

```bash
# Complete reset
make clean-all
make setup

# Or use the fresh command
make fresh  # Warning: destroys all data
```

### Security in Docker

#### Security Features

1. **Non-root containers** - All services run as non-root users
2. **Resource limits** - CPU and memory limits in production
3. **Health checks** - Automatic service health monitoring
4. **Secret management** - Environment-based configuration
5. **Network isolation** - Custom Docker networks
6. **Security scanning** - Built-in Trivy security scans

#### Security Commands

```bash
# Run security scan
make security-scan

# Generate SSL certificates for development
make generate-ssl

# Check for vulnerabilities
docker run --rm -v $(PWD):/app securecodewarrior/docker-security-checker /app
```

### Performance Optimization

#### Production Optimizations

- **OPcache enabled** with optimized settings
- **Multi-stage builds** for smaller images
- **Nginx optimizations** with gzip and caching
- **Database tuning** with connection pooling
- **Redis optimization** with memory limits

## 🔒 Security Considerations

### Best Practices Implemented

1. **UUID Usage**: Prevents enumeration attacks by using UUIDs instead of sequential IDs
2. **Password Hashing**: Uses bcrypt with proper salting
3. **JWT Security**: Configurable expiration and blacklisting
4. **Rate Limiting**: Prevents brute force attacks on login
5. **Input Validation**: Comprehensive validation on all endpoints
6. **CORS Configuration**: Properly configured for cross-origin requests

### Security Headers

Configure your web server to add security headers:

```nginx
add_header X-Frame-Options DENY;
add_header X-Content-Type-Options nosniff;
add_header X-XSS-Protection "1; mode=block";
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
```

## 🏗️ Architecture Decisions

### Why Clean Architecture?

1. **Testability**: Easy to unit test business logic
2. **Maintainability**: Clear separation of concerns
3. **Flexibility**: Easy to swap implementations
4. **Scalability**: Business logic independent of frameworks

### Why JWT?

1. **Stateless**: No server-side session storage required
2. **Scalable**: Works across multiple service instances
3. **Standards-based**: Industry standard with broad support
4. **Flexible**: Custom claims for user information

### Why File-based Token Storage?

1. **Simplicity**: No external dependencies (Redis/Database)
2. **Reliability**: Persistent across application restarts
3. **Performance**: Fast file system operations
4. **Flexibility**: Easy to migrate to other storage systems

## 🚀 Performance Optimization

### Caching Strategy

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

### Database Optimization

- **Indexes**: Proper indexing on email and UUID fields
- **Query Optimization**: Efficient queries with proper eager loading
- **Connection Pooling**: Use connection pooling in production

### Token Storage Optimization

- **File-based Storage**: Organized by user UUID for fast lookup
- **Automatic Cleanup**: Expired tokens are automatically removed
- **Minimal I/O**: Efficient file operations with proper locking

## 🔧 Troubleshooting

### Common Issues

#### JWT Token Issues

```bash
# Regenerate JWT secret
php artisan jwt:secret --force

# Clear JWT blacklist
php artisan jwt:blacklist:clear
```

#### Permission Issues

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Database Issues

```bash
# Reset database
php artisan migrate:fresh --seed

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Debug Mode

Enable debug mode for development:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Log Analysis

```bash
# Check error logs
tail -f storage/logs/laravel.log

# Search for specific errors
grep "ERROR" storage/logs/laravel.log

# Real-time log monitoring
php artisan pail --filter="level:error"
```

## 📚 Additional Resources

### Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [JWT Auth Documentation](https://jwt-auth.readthedocs.io/)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/)
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)

### API Testing

You can test the API using tools like:

- **Postman**: Import the OpenAPI specification
- **curl**: Use the examples provided in this README
- **HTTPie**: Alternative to curl with better syntax

### Example curl Commands

```bash
# Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'

# Validate (replace TOKEN with actual token)
curl -X POST http://localhost:8000/api/auth/validate \
  -H "Authorization: Bearer TOKEN"
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Write tests for all new features
- Update documentation for API changes
- Ensure all tests pass before submitting PR

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:

- Create an issue in the repository
- Check the troubleshooting section
- Review the test files for usage examples

---

**Built with ❤️ using Laravel 12 and Clean Architecture principles**