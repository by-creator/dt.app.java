# PowerShell script to extract PostgreSQL connection details from Heroku DATABASE_URL
# Usage: .\setup-heroku-postgres.ps1 site-dt-production

param(
    [string]$AppName = "site-dt-production"
)

# Get DATABASE_URL
$DATABASE_URL = & heroku config:get DATABASE_URL -a $AppName 2>$null

if ([string]::IsNullOrEmpty($DATABASE_URL)) {
    Write-Host "❌ Error: DATABASE_URL not found for app $AppName" -ForegroundColor Red
    Write-Host "Make sure the PostgreSQL add-on is installed:"
    Write-Host "  heroku addons:create heroku-postgresql:mini -a $AppName"
    exit 1
}

Write-Host "📋 DATABASE_URL found:" -ForegroundColor Cyan
Write-Host "   $DATABASE_URL"
Write-Host ""

# Parse PostgreSQL URL format: postgresql://user:password@host:port/database
if ($DATABASE_URL -match 'postgresql://([^:]+):([^@]+)@([^:]+):(\d+)/(.+)') {
    $PG_USER = $matches[1]
    $PG_PASSWORD = $matches[2]
    $PG_HOST = $matches[3]
    $PG_PORT = $matches[4]
    $PG_DB = $matches[5]

    Write-Host "✅ Extracted connection details:" -ForegroundColor Green
    Write-Host "   PG_USER: $PG_USER"
    Write-Host "   PG_PASSWORD: ****"
    Write-Host "   PG_HOST: $PG_HOST"
    Write-Host "   PG_PORT: $PG_PORT"
    Write-Host "   PG_DB: $PG_DB"
    Write-Host ""

    Write-Host "🔧 Setting Heroku environment variables..." -ForegroundColor Cyan
    & heroku config:set `
        PG_USER=$PG_USER `
        PG_PASSWORD=$PG_PASSWORD `
        PG_HOST=$PG_HOST `
        PG_PORT=$PG_PORT `
        PG_DB=$PG_DB `
        FLYWAY_ENABLED=true `
        DATA_INIT_ENABLED=true `
        -a $AppName

    Write-Host ""
    Write-Host "✅ Configuration complete!" -ForegroundColor Green
    Write-Host "You can now deploy:"
    Write-Host "  git push heroku main:main"
} else {
    Write-Host "❌ Error: Invalid DATABASE_URL format" -ForegroundColor Red
    Write-Host "Expected format: postgresql://user:password@host:port/database"
    exit 1
}
