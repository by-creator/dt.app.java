#!/bin/bash

# O2switch Deployment Script
# This script automates the build and deployment process to O2switch

set -e  # Exit on error

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== DT App O2switch Deployment Script ===${NC}\n"

# Check if required tools are available
check_requirements() {
    echo "Checking requirements..."

    if ! command -v mvn &> /dev/null; then
        echo -e "${RED}Error: Maven is not installed${NC}"
        exit 1
    fi

    if ! command -v scp &> /dev/null; then
        echo -e "${RED}Error: scp command not found${NC}"
        exit 1
    fi

    if ! command -v ssh &> /dev/null; then
        echo -e "${RED}Error: ssh command not found${NC}"
        exit 1
    fi

    echo -e "${GREEN}✓ All requirements met${NC}\n"
}

# Parse arguments
parse_arguments() {
    if [ $# -lt 2 ]; then
        echo -e "${RED}Usage: ./deploy-o2switch.sh <ssh-user@host> <remote-path>${NC}"
        echo ""
        echo "Examples:"
        echo "  ./deploy-o2switch.sh user@example.com /home/dtapp/app"
        echo "  ./deploy-o2switch.sh user@192.168.1.100 /var/www/dtapp"
        exit 1
    fi

    SSH_TARGET="$1"
    REMOTE_PATH="$2"
    JAR_NAME="dt-app-1.0.0.jar"
    LOCAL_JAR="target/$JAR_NAME"
}

# Build the application
build_application() {
    echo -e "${YELLOW}Step 1: Building application with Maven...${NC}"

    if [ ! -f "pom.xml" ]; then
        echo -e "${RED}Error: pom.xml not found. Run this script from the project root directory${NC}"
        exit 1
    fi

    mvn clean package -DskipTests

    if [ ! -f "$LOCAL_JAR" ]; then
        echo -e "${RED}Error: Build failed. JAR file not found at $LOCAL_JAR${NC}"
        exit 1
    fi

    echo -e "${GREEN}✓ Build successful: $LOCAL_JAR${NC}\n"
}

# Upload JAR to server
upload_jar() {
    echo -e "${YELLOW}Step 2: Uploading JAR to O2switch server...${NC}"

    # Check if SSH target is accessible
    if ! ssh -q "$SSH_TARGET" "echo 'SSH connection successful'" 2>/dev/null; then
        echo -e "${RED}Error: Cannot connect to $SSH_TARGET via SSH${NC}"
        echo "Please verify your SSH credentials and host address"
        exit 1
    fi

    # Check if remote directory exists
    if ! ssh "$SSH_TARGET" "[ -d \"$REMOTE_PATH\" ]" 2>/dev/null; then
        echo -e "${YELLOW}Remote directory does not exist. Creating it...${NC}"
        ssh "$SSH_TARGET" "mkdir -p \"$REMOTE_PATH\""
    fi

    # Upload the JAR file
    scp "$LOCAL_JAR" "${SSH_TARGET}:${REMOTE_PATH}/"

    echo -e "${GREEN}✓ JAR uploaded successfully${NC}\n"
}

# Restart the application
restart_application() {
    echo -e "${YELLOW}Step 3: Restarting application on O2switch...${NC}"

    ssh "$SSH_TARGET" << 'SSHCOMMAND'
        # Try systemd first
        if command -v systemctl &> /dev/null; then
            echo "Restarting via systemd..."
            systemctl restart dtapp.service 2>/dev/null || echo "Service not available via systemd"
        fi

        # Check if process is running
        pkill -f "java.*dt-app" || echo "No running Java process found"

        sleep 1

        # Start the application
        if [ -f "$REMOTE_PATH/start.sh" ]; then
            echo "Starting application from script..."
            "$REMOTE_PATH/start.sh"
        else
            echo "Warning: start.sh not found. Please start the application manually."
        fi
SSHCOMMAND

    echo -e "${GREEN}✓ Application restart initiated${NC}\n"
}

# Verify deployment
verify_deployment() {
    echo -e "${YELLOW}Step 4: Verifying deployment...${NC}"

    sleep 3  # Give the application time to start

    # Check if application is running
    ssh "$SSH_TARGET" "ps aux | grep java | grep -v grep" && {
        echo -e "${GREEN}✓ Java process is running${NC}"
    } || {
        echo -e "${YELLOW}! Java process not found. Application may still be starting...${NC}"
    }

    echo -e "${GREEN}✓ Deployment complete!${NC}\n"
}

# Show summary
show_summary() {
    echo -e "${YELLOW}=== Deployment Summary ===${NC}"
    echo "SSH Target: $SSH_TARGET"
    echo "Remote Path: $REMOTE_PATH"
    echo "JAR File: $JAR_NAME"
    echo ""
    echo -e "${GREEN}Application should be running at: http://localhost:8080 (on your server)${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Verify logs: ssh $SSH_TARGET 'tail -f /home/dtapp/logs/application.log'"
    echo "2. Check health: curl http://your-domain.com/actuator/health"
    echo ""
}

# Main execution
main() {
    parse_arguments "$@"
    check_requirements
    build_application
    upload_jar
    restart_application
    verify_deployment
    show_summary
}

# Run main function
main "$@"
