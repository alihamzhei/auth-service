# Authentication and Authorization Microservice

A standalone Laravel-based microservice for authentication and authorization using Clean Architecture principles.

## Deployment Guide

### Prerequisites

- Kubernetes cluster
- Docker registry
- AWS S3 bucket for backups
- PostgreSQL database
- Redis instance

### Environment Variables

The following environment variables need to be set:

- `APP_ENV`: Application environment (production, staging, etc.)
- `APP_KEY`: Laravel application key
- `APP_URL`: URL of the application
- `DB_CONNECTION`: Database connection type (pgsql)
- `DB_HOST`: Database host
- `DB_PORT`: Database port
- `DB_DATABASE`: Database name
- `DB_USERNAME`: Database username
- `DB_PASSWORD`: Database password
- `REDIS_HOST`: Redis host
- `REDIS_PORT`: Redis port
- `JWT_SECRET`: Secret key for JWT tokens

### Deployment Steps

1. Create the namespace:
   ```bash
   kubectl apply -f kubernetes/namespace.yaml
   ```
