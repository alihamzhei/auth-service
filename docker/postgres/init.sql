-- Database initialization script
-- This script runs when the PostgreSQL container starts for the first time

-- Create extensions if needed
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_stat_statements";

-- Set timezone
SET timezone = 'UTC';

-- Create additional databases if needed (for testing, etc.)
-- Uncomment if you need separate test database
-- CREATE DATABASE auth_service_test;

-- Performance optimizations
ALTER SYSTEM SET shared_preload_libraries = 'pg_stat_statements';
ALTER SYSTEM SET max_connections = 200;
ALTER SYSTEM SET shared_buffers = '256MB';
ALTER SYSTEM SET effective_cache_size = '1GB';
ALTER SYSTEM SET maintenance_work_mem = '64MB';
ALTER SYSTEM SET checkpoint_completion_target = 0.9;
ALTER SYSTEM SET wal_buffers = '16MB';
ALTER SYSTEM SET default_statistics_target = 100;
ALTER SYSTEM SET random_page_cost = 1.1;
ALTER SYSTEM SET effective_io_concurrency = 200;

-- Select configuration to confirm
SELECT name, setting FROM pg_settings WHERE name IN (
    'max_connections',
    'shared_buffers',
    'effective_cache_size',
    'maintenance_work_mem'
);

-- Create read-only user for monitoring (optional)
-- CREATE USER auth_readonly WITH PASSWORD 'readonly_password';
-- GRANT CONNECT ON DATABASE auth_service TO auth_readonly;
-- GRANT USAGE ON SCHEMA public TO auth_readonly;
-- GRANT SELECT ON ALL TABLES IN SCHEMA public TO auth_readonly;
-- ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO auth_readonly;