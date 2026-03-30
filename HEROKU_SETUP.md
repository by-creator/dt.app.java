# Heroku Setup Instructions

## Prerequisites

This Java application requires a MySQL database to run. Follow these steps to deploy on Heroku:

### 1. Create Heroku App (if not already created)

```bash
heroku create site-dt-production
```

### 2. Add MySQL Database Add-on

You need to add a MySQL database. Choose one of these options:

#### Option A: ClearDB MySQL (Recommended for small apps)

```bash
heroku addons:create cleardb:ignite -a site-dt-production
```

#### Option B: JawsDB MySQL

```bash
heroku addons:create jawsdb:kitefin -a site-dt-production
```

### 3. Configure Heroku Environment Variables

The database add-on will create a `DATABASE_URL` variable, but we need to parse it into separate variables:

```bash
# For ClearDB, the DATABASE_URL is in format: mysql://user:pass@host/db?reconnect=true
# We need to extract these values and set them as separate env vars

# Get the DATABASE_URL
heroku config:get DATABASE_URL -a site-dt-production

# Set individual database configuration variables
# Replace the values from your DATABASE_URL
heroku config:set MYSQL_HOST=<host> -a site-dt-production
heroku config:set MYSQL_PORT=<port> -a site-dt-production
heroku config:set MYSQL_DB=<database> -a site-dt-production
heroku config:set MYSQL_USER=<username> -a site-dt-production
heroku config:set MYSQL_PASSWORD=<password> -a site-dt-production
```

### 4. Enable Database Initialization on First Deploy

```bash
# Enable Flyway migrations
heroku config:set FLYWAY_ENABLED=true -a site-dt-production

# Enable data initialization (creates admin user, etc.)
heroku config:set DATA_INIT_ENABLED=true -a site-dt-production

# Set your app's base URL
heroku config:set APP_BASE_URL=https://site-dt-production-98050a853413.herokuapp.com -a site-dt-production
```

### 5. Configure Email (Optional but recommended)

The app sends notifications via email. Update the SMTP configuration in `application-heroku.properties` or set environment variables.

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

## Database Schema

The first deployment will:
1. Run Flyway migrations to create the database schema
2. Initialize default admin user (admin@dakar-terminal.com / DakarTerminal2024!)
3. Create necessary authority definitions

## Troubleshooting

### App crashes on startup

Check logs:
```bash
heroku logs --tail -a site-dt-production
```

Common issues:
- **No database**: Ensure MySQL add-on is installed and MYSQL_* variables are set
- **Flyway issues**: If migrations fail, check database permissions and compatibility
- **Port binding**: Heroku automatically sets PORT variable - ensure it's used (already configured)

### Database connection fails

Verify MySQL variables are correctly set:
```bash
heroku config -a site-dt-production
```

### Can't access app

Ensure `/actuator/health` endpoint returns success:
```bash
curl https://site-dt-production-98050a853413.herokuapp.com/actuator/health
```

