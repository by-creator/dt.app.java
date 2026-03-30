# Heroku Setup Instructions

## Prerequisites

This Java application requires a PostgreSQL database to run. Follow these steps to deploy on Heroku:

### 1. Create Heroku App (if not already created)

```bash
heroku create site-dt-production
```

### 2. Add PostgreSQL Database Add-on

Add Heroku PostgreSQL (the standard PostgreSQL add-on for Heroku):

```bash
heroku addons:create heroku-postgresql:mini -a site-dt-production
```

This automatically creates a `DATABASE_URL` environment variable.

### 3. Configure Heroku Environment Variables

Extract the PostgreSQL connection details from the `DATABASE_URL`:

```bash
# Get the DATABASE_URL (format: postgresql://user:password@host:port/dbname)
heroku config:get DATABASE_URL -a site-dt-production

# Parse and set individual PostgreSQL configuration variables
# Example: postgresql://user123:pass456@ec2-1-2-3-4.compute.amazonaws.com:5432/mydb
heroku config:set \
  PG_HOST=<host> \
  PG_PORT=<port> \
  PG_DB=<database> \
  PG_USER=<username> \
  PG_PASSWORD=<password> \
  -a site-dt-production
```

### 4. Enable Database Initialization on First Deploy

```bash
# Enable Flyway migrations (runs SQL migration files)
heroku config:set FLYWAY_ENABLED=true -a site-dt-production

# Enable data initialization (creates admin user, authorities, etc.)
heroku config:set DATA_INIT_ENABLED=true -a site-dt-production

# Set your app's base URL
heroku config:set APP_BASE_URL=https://site-dt-production-98050a853413.herokuapp.com -a site-dt-production
```

### 5. Configure Email (Optional but recommended)

The app sends notifications via email. Update environment variables if needed:

```bash
# Optional: Custom SMTP settings
heroku config:set \
  MAIL_HOST=smtp.gmail.com \
  MAIL_PORT=587 \
  MAIL_USERNAME=your-email@gmail.com \
  MAIL_PASSWORD=your-app-password \
  -a site-dt-production
```

### 6. Deploy

```bash
git push heroku main:main
```

Or if using a specific branch:

```bash
git push heroku claude/fix-heroku-crash-75rVW:main
```

### 7. Monitor Logs

```bash
heroku logs --tail -a site-dt-production
```

### 8. Verify Health

```bash
curl https://site-dt-production-98050a853413.herokuapp.com/actuator/health
```

Expected response (200 OK):
```json
{
  "status": "UP"
}
```

## Database Schema

During first deployment:
1. Flyway migrations create the PostgreSQL database schema (V1-V8)
2. Data initializer creates:
   - Default admin user: `admin@dakar-terminal.com` / `DakarTerminal2024!`
   - Required authority definitions (roles)
   - Default company: DAKAR-TERMINAL
   - Default WiFi settings for GFA module

## PostgreSQL vs MySQL Changes

The application has been converted from MySQL to PostgreSQL:
- **Driver**: `postgresql` instead of `mysql-connector-j`
- **Dialect**: `PostgreSQLDialect` instead of `MySQL8Dialect`
- **SQL Syntax**:
  - `SERIAL`/`BIGSERIAL` instead of `AUTO_INCREMENT`
  - `BOOLEAN` instead of `TINYINT(1)`
  - `BYTEA` instead of `LONGBLOB`
  - `ON CONFLICT DO UPDATE` instead of `ON DUPLICATE KEY UPDATE`
  - Removed MySQL-specific clauses (`ENGINE=InnoDB`, `CHARSET`, `COLLATE`)

## Troubleshooting

### App crashes on startup

Check logs:
```bash
heroku logs --tail -a site-dt-production
```

Common issues:
- **No database**: Ensure Heroku PostgreSQL add-on is installed
- **Wrong credentials**: Verify `PG_HOST`, `PG_USER`, `PG_PASSWORD` match DATABASE_URL
- **Flyway issues**: If migrations fail, check:
  - Database exists and is accessible
  - Migrations are syntactically correct for PostgreSQL
  - Run: `heroku pg:psql -a site-dt-production` to inspect database

### View database status

```bash
# View add-ons
heroku addons -a site-dt-production

# Connect to PostgreSQL console
heroku pg:psql -a site-dt-production

# List tables
\dt

# Check migrations
SELECT * FROM flyway_schema_history;
```

### Database connection fails

Verify PostgreSQL variables:
```bash
heroku config -a site-dt-production | grep PG_
```

Reset database (⚠️ **DESTRUCTIVE** - clears all data):
```bash
heroku pg:reset -a site-dt-production
heroku restart -a site-dt-production
```

### Can't access app

Ensure health endpoint responds:
```bash
curl https://site-dt-production-98050a853413.herokuapp.com/actuator/health
```

Check application logs for Spring Boot startup errors:
```bash
heroku logs --tail -a site-dt-production | grep -E "ERROR|Exception"
```

