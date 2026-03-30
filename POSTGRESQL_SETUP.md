# PostgreSQL Configuration Guide

## Installation

### Linux (Ubuntu/Debian)

```bash
sudo apt-get update
sudo apt-get install postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

### macOS (Homebrew)

```bash
brew install postgresql
brew services start postgresql
```

### Windows

Download and install from [postgresql.org](https://www.postgresql.org/download/windows/)

## Local Setup

### 1. Create Database and User

```bash
# Connect to PostgreSQL
sudo -u postgres psql

# In psql console:
CREATE DATABASE dtapp;
CREATE USER dtapp_user WITH PASSWORD 'your-secure-password';
ALTER ROLE dtapp_user SET client_encoding TO 'utf8';
ALTER ROLE dtapp_user SET default_transaction_isolation TO 'read committed';
ALTER ROLE dtapp_user SET default_transaction_deferrable TO on;
ALTER ROLE dtapp_user SET default_transaction_isolation TO 'read committed';
GRANT ALL PRIVILEGES ON DATABASE dtapp TO dtapp_user;
\q
```

### 2. Update Application Properties

Edit `src/main/resources/application.properties`:

```properties
# Database Configuration
spring.datasource.url=jdbc:postgresql://localhost:5432/dtapp
spring.datasource.username=dtapp_user
spring.datasource.password=your-secure-password
spring.datasource.driver-class-name=org.postgresql.Driver

# JPA/Hibernate Configuration
spring.jpa.database-platform=org.hibernate.dialect.PostgreSQLDialect
spring.jpa.hibernate.ddl-auto=validate
```

### 3. Verify Connection

Run the application:
```bash
mvn clean spring-boot:run
```

Check logs for successful connection.

## Database Queries

### Connect to Database

```bash
psql -U dtapp_user -d dtapp -h localhost
```

### Useful Commands

```sql
-- List all tables
\dt

-- List table structure
\d table_name

-- View migrations status
SELECT * FROM flyway_schema_history;

-- Count rows in a table
SELECT COUNT(*) FROM users;

-- View users
SELECT id, username, email, enabled FROM users;

-- Reset all data (⚠️ DESTRUCTIVE)
DROP SCHEMA public CASCADE;
CREATE SCHEMA public;
```

## Flyway Migrations

Migrations are located in `src/main/resources/db/migration/`.

- **V1**: Initial schema (users, authorities, compagnies)
- **V2**: Blocage items table
- **V3**: Codifications table
- **V4**: Audit logs table
- **V5**: Rattachement BLS table
- **V6**: Tiers Unify table
- **V7**: GFA tables (services, guichets, agents, tickets, wifi_settings)
- **V8**: Satisfaction responses table

To enable automatic migrations on startup:
```properties
spring.flyway.enabled=true
```

## Troubleshooting

### Connection Refused

Ensure PostgreSQL is running:
```bash
# Linux
systemctl status postgresql

# macOS
brew services list | grep postgresql
```

### Migrations Failed

Check migration compatibility:
```bash
# View migration history
psql -U dtapp_user -d dtapp -c "SELECT * FROM flyway_schema_history;"

# View current errors in logs
```

### Reset Database

```bash
# Drop and recreate
psql -U postgres -c "DROP DATABASE dtapp;"
psql -U postgres -c "CREATE DATABASE dtapp;"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE dtapp TO dtapp_user;"

# Application will recreate on next startup if flyway.enabled=true
```

## Performance Optimization

For development, you can optimize PostgreSQL:

```sql
-- Increase connection count (if needed)
ALTER SYSTEM SET max_connections = 200;
SELECT pg_reload_conf();

-- Check current connections
SELECT datname, count(*) as connections
FROM pg_stat_activity
GROUP BY datname;
```
