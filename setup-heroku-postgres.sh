#!/bin/bash

# Script to extract PostgreSQL connection details from Heroku DATABASE_URL
# Usage: ./setup-heroku-postgres.sh site-dt-production

APP_NAME=${1:-site-dt-production}

# Get DATABASE_URL
DATABASE_URL=$(heroku config:get DATABASE_URL -a $APP_NAME)

if [ -z "$DATABASE_URL" ]; then
    echo "❌ Error: DATABASE_URL not found for app $APP_NAME"
    echo "Make sure the PostgreSQL add-on is installed:"
    echo "  heroku addons:create heroku-postgresql:mini -a $APP_NAME"
    exit 1
fi

echo "📋 DATABASE_URL found:"
echo "   $DATABASE_URL"
echo ""

# Parse PostgreSQL URL format: postgresql://user:password@host:port/database
# Extract components using regex
if [[ $DATABASE_URL =~ postgresql://([^:]+):([^@]+)@([^:]+):([^/]+)/(.+) ]]; then
    PG_USER="${BASH_REMATCH[1]}"
    PG_PASSWORD="${BASH_REMATCH[2]}"
    PG_HOST="${BASH_REMATCH[3]}"
    PG_PORT="${BASH_REMATCH[4]}"
    PG_DB="${BASH_REMATCH[5]}"

    echo "✅ Extracted connection details:"
    echo "   PG_USER: $PG_USER"
    echo "   PG_PASSWORD: ****"
    echo "   PG_HOST: $PG_HOST"
    echo "   PG_PORT: $PG_PORT"
    echo "   PG_DB: $PG_DB"
    echo ""

    echo "🔧 Setting Heroku environment variables..."
    heroku config:set \
        PG_USER="$PG_USER" \
        PG_PASSWORD="$PG_PASSWORD" \
        PG_HOST="$PG_HOST" \
        PG_PORT="$PG_PORT" \
        PG_DB="$PG_DB" \
        FLYWAY_ENABLED=true \
        DATA_INIT_ENABLED=true \
        -a $APP_NAME

    echo ""
    echo "✅ Configuration complete!"
    echo "You can now deploy:"
    echo "  git push heroku main:main"
else
    echo "❌ Error: Invalid DATABASE_URL format"
    echo "Expected format: postgresql://user:password@host:port/database"
    exit 1
fi
