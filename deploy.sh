#!/bin/bash

# Auto-deploy script for QLSS application
# This script automatically detects environment and deploys accordingly

echo "🚀 Starting deployment process..."

# Configuration
LOCAL_DIR="."
REMOTE_HOST="your-server-ip-or-domain"  # Change this to your server
REMOTE_USER="your-username"              # Change this to your username
REMOTE_DIR="/var/www/html/qlss"         # Change this to your remote directory
BACKUP_DIR="/var/www/html/qlss_backup"  # Change this to your backup directory

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if required tools are installed
check_requirements() {
    print_status "Checking requirements..."
    
    if ! command -v rsync &> /dev/null; then
        print_error "rsync is not installed. Please install it first."
        exit 1
    fi
    
    if ! command -v ssh &> /dev/null; then
        print_error "ssh is not installed. Please install it first."
        exit 1
    fi
    
    print_success "Requirements check passed"
}

# Create backup on remote server
create_backup() {
    print_status "Creating backup on remote server..."
    
    ssh $REMOTE_USER@$REMOTE_HOST << EOF
        if [ -d "$REMOTE_DIR" ]; then
            echo "Creating backup..."
            sudo mkdir -p $BACKUP_DIR
            sudo cp -r $REMOTE_DIR $BACKUP_DIR/qlss_\$(date +%Y%m%d_%H%M%S)
            echo "Backup created successfully"
        else
            echo "Remote directory does not exist, skipping backup"
        fi
EOF
    
    if [ $? -eq 0 ]; then
        print_success "Backup created successfully"
    else
        print_warning "Backup creation failed, but continuing deployment"
    fi
}

# Deploy to production
deploy_production() {
    print_status "Deploying to PRODUCTION server..."
    
    # Create production environment file
    echo "ENVIRONMENT=production" > .env.production
    echo "APP_ENV=production" >> .env.production
    
    # Copy production database config
    if [ -f "db_production.php" ]; then
        print_status "Using production database configuration"
    else
        print_warning "db_production.php not found, will use local database config"
    fi
    
    # Deploy files
    rsync -avz --delete \
        --exclude='.git/' \
        --exclude='.gitignore' \
        --exclude='README.md' \
        --exclude='deploy.sh' \
        --exclude='*.sql' \
        --exclude='logs/' \
        --exclude='cache/' \
        --exclude='tmp/' \
        --exclude='.DS_Store' \
        --exclude='*.log' \
        $LOCAL_DIR/ $REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR/
    
    if [ $? -eq 0 ]; then
        print_success "Files deployed successfully"
    else
        print_error "File deployment failed"
        exit 1
    fi
    
    # Set proper permissions
    ssh $REMOTE_USER@$REMOTE_HOST << EOF
        sudo chown -R www-data:www-data $REMOTE_DIR
        sudo chmod -R 755 $REMOTE_DIR
        sudo chmod -R 644 $REMOTE_DIR/*.php
        sudo chmod -R 644 $REMOTE_DIR/*.css
        sudo chmod -R 644 $REMOTE_DIR/*.js
        echo "Permissions set successfully"
EOF
    
    # Clean up local environment file
    rm -f .env.production
    
    print_success "Production deployment completed!"
}

# Deploy to development
deploy_development() {
    print_status "Deploying to DEVELOPMENT server..."
    
    # Create development environment file
    echo "ENVIRONMENT=development" > .env.local
    echo "APP_ENV=development" >> .env.local
    
    # Deploy files
    rsync -avz --delete \
        --exclude='.git/' \
        --exclude='.gitignore' \
        --exclude='README.md' \
        --exclude='deploy.sh' \
        --exclude='*.sql' \
        --exclude='logs/' \
        --exclude='cache/' \
        --exclude='tmp/' \
        --exclude='.DS_Store' \
        --exclude='*.log' \
        $LOCAL_DIR/ $REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR/
    
    if [ $? -eq 0 ]; then
        print_success "Files deployed successfully"
    else
        print_error "File deployment failed"
        exit 1
    fi
    
    # Clean up local environment file
    rm -f .env.local
    
    print_success "Development deployment completed!"
}

# Main deployment logic
main() {
    print_status "Starting deployment process..."
    
    # Check requirements
    check_requirements
    
    # Ask user for deployment type
    echo ""
    echo "Choose deployment type:"
    echo "1) Production (live server)"
    echo "2) Development (test server)"
    echo "3) Cancel"
    echo ""
    read -p "Enter your choice (1-3): " choice
    
    case $choice in
        1)
            print_status "Production deployment selected"
            create_backup
            deploy_production
            ;;
        2)
            print_status "Development deployment selected"
            create_backup
            deploy_development
            ;;
        3)
            print_status "Deployment cancelled"
            exit 0
            ;;
        *)
            print_error "Invalid choice. Please run the script again."
            exit 1
            ;;
    esac
    
    print_success "Deployment process completed successfully!"
}

# Run main function
main "$@"
