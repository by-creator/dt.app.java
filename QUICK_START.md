# Quick Start Guide

## 🚀 Local Development Setup

### 1. Install PostgreSQL

#### Windows
Download from https://www.postgresql.org/download/windows/

**During installation:**
- Remember the password you set for user `postgres`
- Keep port as `5432`

#### macOS (Homebrew)
```bash
brew install postgresql
brew services start postgresql
```

#### Linux (Ubuntu/Debian)
```bash
sudo apt-get update
sudo apt-get install postgresql postgresql-contrib
sudo systemctl start postgresql
```

### 2. Create Database and User

```bash
# Connect to PostgreSQL
psql -U postgres

# In psql console, run:
CREATE DATABASE dtapp;
CREATE USER dtapp_user WITH PASSWORD 'dtapp123';
ALTER ROLE dtapp_user SET client_encoding TO 'utf8';
ALTER ROLE dtapp_user SET default_transaction_isolation TO 'read committed';
GRANT ALL PRIVILEGES ON DATABASE dtapp TO dtapp_user;
\q
```

### 3. Update Local Configuration

Edit `src/main/resources/application.properties`:

```properties
spring.datasource.url=jdbc:postgresql://localhost:5432/dtapp
spring.datasource.username=dtapp_user
spring.datasource.password=dtapp123
spring.flyway.enabled=false
app.data-initializer.enabled=false
```

### 4. Build and Run

```bash
# Build the project
mvn clean install

# Run the application
mvn spring-boot:run
```

Access the app at: http://localhost:8080

---

## 🌍 Heroku Deployment Setup

### Prerequisites

1. **Heroku CLI** installed
2. **Git** repository with your code
3. Logged in to Heroku: `heroku login`

### Step-by-Step Deployment

#### 1. Create Heroku App

```bash
heroku create site-dt-production
```

#### 2. Add PostgreSQL Database

```bash
heroku addons:create heroku-postgresql:mini -a site-dt-production
```

#### 3. Extract and Configure Database Variables

**On macOS/Linux:**
```bash
chmod +x setup-heroku-postgres.sh
./setup-heroku-postgres.sh site-dt-production
```

**On Windows (PowerShell):**
```powershell
.\setup-heroku-postgres.ps1 site-dt-production
```

**Or manually:**
```bash
# Get the DATABASE_URL
heroku config:get DATABASE_URL -a site-dt-production

# Example output: postgresql://user:password@host:5432/database

# Extract and set variables:
heroku config:set \
  PG_USER=user \
  PG_PASSWORD=password \
  PG_HOST=host.amazonaws.com \
  PG_PORT=5432 \
  PG_DB=database \
  FLYWAY_ENABLED=true \
  DATA_INIT_ENABLED=true \
  -a site-dt-production
```

#### 4. Configure App Base URL

```bash
heroku config:set APP_BASE_URL=https://site-dt-production.herokuapp.com -a site-dt-production
```

#### 5. Deploy

```bash
git push heroku main:main
```

Or if using a specific branch:
```bash
git push heroku claude/fix-heroku-crash-75rVW:main
```

#### 6. Monitor Deployment

```bash
# Watch logs in real-time
heroku logs --tail -a site-dt-production

# Check app status
curl https://site-dt-production.herokuapp.com/actuator/health
```

---

## 🛠️ Common Issues & Solutions

### Local: PostgreSQL Connection Refused

**Problem:** `Connection to localhost:5432 refused`

**Solutions:**

```bash
# Check if PostgreSQL is running
# macOS:
brew services list | grep postgresql

# Linux:
systemctl status postgresql

# Windows:
Get-Service postgresql
```

**Start PostgreSQL:**
```bash
# macOS
brew services start postgresql

# Linux
sudo systemctl start postgresql

# Windows - via Services app or command:
net start postgresql
```

### Local: Wrong Password/User

```bash
# Reset postgres user password
psql -U postgres -c "ALTER USER postgres WITH PASSWORD 'newpassword';"

# Or create a new user
psql -U postgres -c "CREATE USER dtapp_user WITH PASSWORD 'dtapp123';"
```

### Heroku: Invalid JDBC URL

**Problem:** `Invalid JDBC URL: jdbc:postgres://...`

**Solution:** JDBC URL must use `postgresql` not `postgres`:
```
✅ jdbc:postgresql://host:5432/db
❌ jdbc:postgres://host:5432/db
```

### Heroku: Flyway Migration Errors

```bash
# Check migration history
heroku pg:psql -a site-dt-production -c "SELECT * FROM flyway_schema_history;"

# View detailed errors
heroku logs --tail -a site-dt-production | grep -i flyway

# Reset database if needed (⚠️ DESTRUCTIVE)
heroku pg:reset -a site-dt-production
heroku restart -a site-dt-production
```

### Heroku: Database Not Found

Verify add-on is installed:
```bash
heroku addons -a site-dt-production
```

Expected output: `heroku-postgresql (heroku-postgresql-xxxx) free`

---

## 📚 Useful Commands

### Local PostgreSQL

```bash
# Connect to database
psql -U dtapp_user -d dtapp -h localhost

# List databases
\l

# List tables
\dt

# Exit psql
\q

# View table structure
\d table_name

# Count rows
SELECT COUNT(*) FROM users;
```

### Heroku PostgreSQL

```bash
# Connect to Heroku database
heroku pg:psql -a site-dt-production

# Backup database
heroku pg:backups:capture -a site-dt-production

# List backups
heroku pg:backups -a site-dt-production

# Restore from backup
heroku pg:backups:restore BACKUP_ID -a site-dt-production

# View logs
heroku logs --tail -a site-dt-production
```

---

## 🔗 Default Credentials

**Local Development:**
- Username: `admin@dakar-terminal.com`
- Password: `DakarTerminal2024!`

These are created by `DataInitializer` on first run.

---

## 📖 Additional Documentation

- [POSTGRESQL_SETUP.md](./POSTGRESQL_SETUP.md) - Detailed PostgreSQL setup
- [HEROKU_SETUP.md](./HEROKU_SETUP.md) - Detailed Heroku deployment
- [PostgreSQL Docs](https://www.postgresql.org/docs/)
- [Heroku Docs](https://devcenter.heroku.com/)
