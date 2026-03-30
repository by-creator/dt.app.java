# O2switch Deployment Guide

This guide provides step-by-step instructions to deploy the DT App (Spring Boot Java application) on O2switch hosting.

## Prerequisites

- O2switch hosting account with SSH access enabled
- Java 17 or higher installed on your O2switch server
- MySQL 8.0 or higher available on O2switch
- Maven installed locally (for building the application)
- SSH client installed locally
- Domain configured and pointing to your O2switch server

## Step 1: Access Your O2switch Server via SSH

```bash
ssh username@your-server-ip
# or
ssh username@your-domain.com
```

Replace:
- `username` - Your O2switch account username (find in your hosting panel)
- `your-server-ip` - Your server IP address or domain

## Step 2: Create Application Directory Structure

```bash
# Create directory for the application
mkdir -p /home/dtapp/app
mkdir -p /home/dtapp/uploads
mkdir -p /home/dtapp/logs
mkdir -p /home/dtapp/config

cd /home/dtapp/app
```

## Step 3: Create MySQL Database

Connect to your MySQL database:

```bash
mysql -u root -p
# or if you have a specific MySQL user:
mysql -h localhost -u your_mysql_user -p
```

Then run:

```sql
-- Create database
CREATE DATABASE dtapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create application user with strong password
CREATE USER 'dtapp_user'@'localhost' IDENTIFIED BY 'StrongPassword123!@#';

-- Grant permissions
GRANT ALL PRIVILEGES ON dtapp.* TO 'dtapp_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

Save your MySQL credentials (you'll need them later):
- **Host**: localhost
- **Port**: 3306 (default)
- **Database**: dtapp
- **Username**: dtapp_user
- **Password**: StrongPassword123!@# (change this!)

## Step 4: Build the Application Locally

On your local machine:

```bash
# Navigate to project directory
cd dt.app.java

# Build with Maven
mvn clean package -DskipTests

# Verify the JAR was created
ls -lh target/dt-app-1.0.0.jar
```

## Step 5: Upload the Application to O2switch

Transfer the compiled JAR file to your O2switch server using SCP:

```bash
# From your local machine
scp target/dt-app-1.0.0.jar username@your-domain.com:/home/dtapp/app/

# Verify upload
ssh username@your-domain.com "ls -lh /home/dtapp/app/dt-app-1.0.0.jar"
```

## Step 6: Create Environment Configuration File

SSH into your server and create the environment file:

```bash
ssh username@your-domain.com
```

Create `/home/dtapp/config/.env.o2switch`:

```bash
cat > /home/dtapp/config/.env.o2switch << 'EOF'
# Database Configuration
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_DB=dtapp
MYSQL_USER=dtapp_user
MYSQL_PASSWORD=StrongPassword123!@#

# Application Configuration
PORT=8080
APP_BASE_URL=https://your-domain.com
UPLOAD_DIR=/home/dtapp/uploads
LOG_DIR=/home/dtapp/logs

# Flyway & Data Initialization (set to true on first deployment only)
FLYWAY_ENABLED=true
DATA_INIT_ENABLED=true

# Mail Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_TO=sn004-proforma@dakar-terminal.com
MAIL_TO_REMISE=sn004-remise.facturation@dakar-terminal.com

# Security
SECURITY_PASSWORD=ChangeThisSecurePassword123!
EOF

# Secure the file (readable only by owner)
chmod 600 /home/dtapp/config/.env.o2switch
```

**Important**: Replace all placeholder values with your actual credentials.

## Step 7: Create Startup Script

Create `/home/dtapp/app/start.sh`:

```bash
cat > /home/dtapp/app/start.sh << 'EOF'
#!/bin/bash

# Load environment variables
source /home/dtapp/config/.env.o2switch

# Export variables for the Java application
export JAVA_OPTS="-Xmx512m -Xms256m -XX:+UseG1GC"

# Start the application
cd /home/dtapp/app

java $JAVA_OPTS \
  -Dspring.profiles.active=o2switch \
  -Dserver.port=$PORT \
  -DMYSQL_HOST=$MYSQL_HOST \
  -DMYSQL_PORT=$MYSQL_PORT \
  -DMYSQL_DB=$MYSQL_DB \
  -DMYSQL_USER=$MYSQL_USER \
  -DMYSQL_PASSWORD="$MYSQL_PASSWORD" \
  -DAPP_BASE_URL=$APP_BASE_URL \
  -DUPLOAD_DIR=$UPLOAD_DIR \
  -DLOG_DIR=$LOG_DIR \
  -DFLYWAY_ENABLED=$FLYWAY_ENABLED \
  -DDATA_INIT_ENABLED=$DATA_INIT_ENABLED \
  -DMAIL_HOST=$MAIL_HOST \
  -DMAIL_PORT=$MAIL_PORT \
  -DMAIL_USERNAME="$MAIL_USERNAME" \
  -DMAIL_PASSWORD="$MAIL_PASSWORD" \
  -DMAIL_TO=$MAIL_TO \
  -DMAIL_TO_REMISE=$MAIL_TO_REMISE \
  -DSECURITY_PASSWORD="$SECURITY_PASSWORD" \
  -jar dt-app-1.0.0.jar >> /home/dtapp/logs/application.log 2>&1 &

echo "Application started (PID: $!)"
EOF

chmod +x /home/dtapp/app/start.sh
```

## Step 8: Configure Systemd Service (Recommended)

For automatic startup and management, create a systemd service file:

```bash
# On your O2switch server, create the service file
sudo bash << 'EOF'
cat > /etc/systemd/system/dtapp.service << 'SYSEOF'
[Unit]
Description=DT App - Spring Boot Application
After=network.target mysql.service

[Service]
Type=simple
User=dtapp
WorkingDirectory=/home/dtapp/app
EnvironmentFile=/home/dtapp/config/.env.o2switch
ExecStart=/home/dtapp/app/start.sh
Restart=on-failure
RestartSec=10

# JVM Memory settings
Environment="JAVA_OPTS=-Xmx512m -Xms256m -XX:+UseG1GC"

[Install]
WantedBy=multi-user.target
SYSEOF

# Reload systemd daemon
systemctl daemon-reload

# Enable service to start on boot
systemctl enable dtapp.service

# Start the service
systemctl start dtapp.service

# Check status
systemctl status dtapp.service
EOF
```

To view logs:
```bash
journalctl -u dtapp.service -f
```

## Step 9: Configure Reverse Proxy (Apache/Nginx)

### For Apache (if using Apache):

Create/edit `/home/dtapp/config/httpd.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com

    # Redirect HTTP to HTTPS
    Redirect permanent / https://your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    ServerAlias www.your-domain.com

    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key

    ProxyPreserveHost On
    ProxyPass / http://localhost:8080/
    ProxyPassReverse / http://localhost:8080/

    # Prevent direct access to sensitive endpoints
    <Location ~ "^/actuator/">
        Require all denied
    </Location>
</VirtualHost>
```

Then notify your O2switch support to apply this configuration.

### For Nginx (if available):

Create/edit `/home/dtapp/config/nginx.conf`:

```nginx
upstream dtapp {
    server localhost:8080;
}

server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    client_max_body_size 50M;

    location / {
        proxy_pass http://dtapp;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    # Block actuator endpoints
    location ~ ^/actuator/ {
        deny all;
    }
}
```

## Step 10: Configure SSL Certificate

O2switch usually provides free SSL certificates. Install the certificate for your domain:

```bash
# On O2switch server
# The certificate is typically at:
# /etc/ssl/certs/your-domain.com.crt
# /etc/ssl/private/your-domain.com.key

# Update the proxy configuration with correct paths
```

## Step 11: Verify Application is Running

```bash
# Check if the process is running
ps aux | grep java

# Check logs
tail -f /home/dtapp/logs/application.log

# Test the application health endpoint
curl http://localhost:8080/actuator/health
```

## Step 12: Post-Deployment Configuration

### First Deployment Only:

After the application starts:

1. **Database migrations will run automatically** (Flyway is enabled in the configuration)
2. **Admin user will be created** with the provided SECURITY_PASSWORD
3. **Default data will be initialized** (companies, authorities, etc.)

Monitor the logs during first startup to ensure migrations complete successfully:

```bash
tail -f /home/dtapp/logs/application.log | grep -E "Flyway|migration|ERROR"
```

### Disable Initialization for Future Deployments:

After the first deployment succeeds, update your environment file to prevent re-initialization:

```bash
# Edit /home/dtapp/config/.env.o2switch
nano /home/dtapp/config/.env.o2switch

# Change these lines:
FLYWAY_ENABLED=false      # Disable Flyway after migrations run
DATA_INIT_ENABLED=false   # Disable data initialization

# Restart the service
systemctl restart dtapp.service
```

## Deployment Updates

To update the application with a new version:

```bash
# 1. Build new JAR locally
mvn clean package -DskipTests

# 2. Upload to O2switch
scp target/dt-app-1.0.0.jar username@your-domain.com:/home/dtapp/app/

# 3. SSH to server and restart
ssh username@your-domain.com "systemctl restart dtapp.service"

# 4. Monitor the startup
ssh username@your-domain.com "journalctl -u dtapp.service -f"
```

## Monitoring & Maintenance

### Check Application Health:

```bash
curl https://your-domain.com/actuator/health
```

Expected response:
```json
{
  "status": "UP"
}
```

### View Application Metrics:

```bash
curl https://your-domain.com/actuator/metrics
```

### Database Backup:

```bash
# Backup MySQL database
mysqldump -u dtapp_user -p dtapp > /home/dtapp/backups/dtapp_$(date +%Y%m%d_%H%M%S).sql

# Or setup automated daily backups
crontab -e
# Add: 0 2 * * * mysqldump -u dtapp_user -p"password" dtapp > /home/dtapp/backups/dtapp_$(date +\%Y\%m\%d).sql
```

### Upload Directory Backup:

```bash
# Backup uploads directory
tar -czf /home/dtapp/backups/uploads_$(date +%Y%m%d).tar.gz /home/dtapp/uploads/
```

## Troubleshooting

### Application won't start

1. **Check Java installation**:
   ```bash
   java -version
   ```

2. **Check logs for errors**:
   ```bash
   journalctl -u dtapp.service -n 100
   ```

3. **Verify MySQL connection**:
   ```bash
   mysql -h localhost -u dtapp_user -p -e "SHOW DATABASES;"
   ```

### Database migration failed

1. **Check Flyway status**:
   ```bash
   mysql -u dtapp_user -p dtapp -e "SELECT * FROM flyway_schema_history;"
   ```

2. **View detailed logs**:
   ```bash
   grep -i flyway /home/dtapp/logs/application.log
   ```

3. **Manual database repair** (if needed):
   ```bash
   # Backup first!
   mysqldump -u dtapp_user -p dtapp > backup.sql

   # Clear failed migration
   mysql -u dtapp_user -p dtapp -e "DELETE FROM flyway_schema_history WHERE success = false;"

   # Restart application
   systemctl restart dtapp.service
   ```

### High memory usage

Adjust JVM settings in `/home/dtapp/app/start.sh`:

```bash
# Change from:
export JAVA_OPTS="-Xmx512m -Xms256m -XX:+UseG1GC"

# To larger values if needed:
export JAVA_OPTS="-Xmx1024m -Xms512m -XX:+UseG1GC"
```

### Email not sending

1. **Verify SMTP credentials**:
   ```bash
   # Check environment file
   cat /home/dtapp/config/.env.o2switch | grep MAIL_
   ```

2. **Test connection manually**:
   ```bash
   telnet smtp.gmail.com 587
   ```

3. **Check logs for mail errors**:
   ```bash
   grep -i mail /home/dtapp/logs/application.log
   ```

## Security Recommendations

1. **Change all default passwords** in `/home/dtapp/config/.env.o2switch`
2. **Restrict access to configuration files**:
   ```bash
   chmod 600 /home/dtapp/config/.env.o2switch
   chmod 700 /home/dtapp/config/
   ```
3. **Enable firewall rules** on O2switch to limit port 8080 access
4. **Regular backups** of database and uploads
5. **Monitor logs regularly** for suspicious activity
6. **Update Java** when security patches are available
7. **Use HTTPS only** (force redirect HTTP to HTTPS)

## Support

For O2switch-specific issues, contact your O2switch support team.
For application-specific issues, check the Spring Boot logs or GitHub issues.

---

**Version**: 1.0
**Last Updated**: 2026-03-30
