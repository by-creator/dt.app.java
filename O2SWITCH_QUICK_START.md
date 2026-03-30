# O2switch Deployment - Quick Start Guide

## ⚡ TL;DR - 5 Minute Setup

### 1. Build & Deploy (on your local machine)
```bash
# Build the application
mvn clean package -DskipTests

# Deploy to O2switch
./deploy-o2switch.sh your-username@your-domain.com /home/dtapp/app
```

### 2. SSH to your server
```bash
ssh your-username@your-domain.com
```

### 3. Create MySQL database
```bash
mysql -u root -p << 'EOF'
CREATE DATABASE dtapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dtapp_user'@'localhost' IDENTIFIED BY 'StrongPassword123!@#';
GRANT ALL PRIVILEGES ON dtapp.* TO 'dtapp_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### 4. Create configuration
```bash
# Create directories
mkdir -p /home/dtapp/{app,config,uploads,logs,backups}

# Copy the example environment file
curl -o /home/dtapp/config/.env.o2switch \
  https://raw.githubusercontent.com/your-repo/dt.app.java/claude/deploy-o2switch-Z8Ub0/.env.o2switch.example

# Edit with your values
nano /home/dtapp/config/.env.o2switch
```

### 5. Create startup script
```bash
cat > /home/dtapp/app/start.sh << 'EOF'
#!/bin/bash
source /home/dtapp/config/.env.o2switch
export JAVA_OPTS="-Xmx512m -Xms256m -XX:+UseG1GC"
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
EOF

chmod +x /home/dtapp/app/start.sh
```

### 6. Start the application
```bash
/home/dtapp/app/start.sh

# Check logs
tail -f /home/dtapp/logs/application.log
```

### 7. Verify it works
```bash
# From another terminal
curl http://localhost:8080/actuator/health
```

## 📋 Configuration Checklist

Before first start, verify in `/home/dtapp/config/.env.o2switch`:

- [ ] MySQL credentials are correct
- [ ] APP_BASE_URL points to your domain
- [ ] MAIL_* settings are configured
- [ ] SECURITY_PASSWORD is changed from default
- [ ] UPLOAD_DIR exists and is writable
- [ ] FLYWAY_ENABLED=true (first deployment only)
- [ ] DATA_INIT_ENABLED=true (first deployment only)

## 🔧 Systemd Service Setup (Optional but Recommended)

For automatic startup and monitoring:

```bash
sudo bash << 'EOF'
cat > /etc/systemd/system/dtapp.service << 'SYSEOF'
[Unit]
Description=DT App
After=network.target

[Service]
Type=simple
User=dtapp
WorkingDirectory=/home/dtapp/app
EnvironmentFile=/home/dtapp/config/.env.o2switch
ExecStart=/home/dtapp/app/start.sh
Restart=on-failure
RestartSec=10

[Install]
WantedBy=multi-user.target
SYSEOF

systemctl daemon-reload
systemctl enable dtapp.service
systemctl start dtapp.service
```

View logs: `journalctl -u dtapp.service -f`

## 📊 Useful Commands

```bash
# Check if running
ps aux | grep java | grep -v grep

# View logs (manual start)
tail -f /home/dtapp/logs/application.log

# View logs (systemd)
journalctl -u dtapp.service -f

# Stop the application
pkill -f "java.*dt-app"

# Test database connection
mysql -h localhost -u dtapp_user -p dtapp -e "SELECT 1;"

# View database migrations
mysql -u dtapp_user -p dtapp -e "SELECT * FROM flyway_schema_history;"

# Backup database
mysqldump -u dtapp_user -p dtapp > /home/dtapp/backups/dtapp_backup_$(date +%Y%m%d).sql
```

## 🔄 After First Successful Deployment

**IMPORTANT:** After the application starts successfully and you see the logs show "Started DtApp in X seconds", update your environment file:

```bash
# Edit the environment file
nano /home/dtapp/config/.env.o2switch

# Change these lines:
FLYWAY_ENABLED=false
DATA_INIT_ENABLED=false

# Restart the application
pkill -f "java.*dt-app"
sleep 2
/home/dtapp/app/start.sh
```

## 🆘 Troubleshooting

### Application won't start
```bash
# Check logs for errors
tail -100 /home/dtapp/logs/application.log | grep -E "ERROR|Exception"

# Verify Java is installed
java -version

# Check MySQL is running
mysql -u root -p -e "SELECT 1;"
```

### Database migration failed
```bash
# Check migration history
mysql -u dtapp_user -p dtapp -e "SELECT * FROM flyway_schema_history;"

# Check specific error
grep -i flyway /home/dtapp/logs/application.log
```

### Port 8080 already in use
```bash
# Find what's using port 8080
lsof -i :8080
# or
ss -tlnp | grep 8080

# Kill the process
kill -9 <PID>
```

### Email not working
```bash
# Verify SMTP credentials
cat /home/dtapp/config/.env.o2switch | grep MAIL_

# Test SMTP connection (if telnet available)
telnet smtp.gmail.com 587
```

## 📚 For More Details

See **O2SWITCH_SETUP.md** for complete step-by-step instructions with screenshots and advanced configuration.

---

**Need help?** Check the full documentation in O2SWITCH_SETUP.md
