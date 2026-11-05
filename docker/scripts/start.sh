#!/bin/bash
set -e

echo "🚀 Polyglot DB stack - Startup Script"
echo "============================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker is not running. Please start Docker first.${NC}"
    exit 1
fi

echo -e "${YELLOW}📦 Building and starting containers...${NC}"
docker compose up -d --build --remove-orphans

echo -e "${YELLOW}⏳ Waiting for services to be ready (30 seconds)...${NC}"
sleep 30

# Initialize MongoDB Replica Set
echo -e "${YELLOW}🔧 Initializing MongoDB Replica Set...${NC}"
docker compose exec -T mongo-primary mongosh --eval "
try {
    rs.initiate({
        _id: 'rs0',
        members: [
            { _id: 0, host: 'mongo-primary:27017', priority: 2 },
            { _id: 1, host: 'mongo-secondary:27017', priority: 1 },
            { _id: 2, host: 'mongo-arbiter:27017', arbiterOnly: true }
        ]
    });
    print('✅ MongoDB Replica Set initialized');
} catch (e) {
    if (e.codeName === 'AlreadyInitialized') {
        print('ℹ️  MongoDB Replica Set already initialized');
    } else {
        throw e;
    }
}
"

echo -e "${YELLOW}⏳ Waiting for MongoDB election (10 seconds)...${NC}"
sleep 10

# Setup MariaDB Replication
echo -e "${YELLOW}🔧 Setting up MariaDB Replication...${NC}"
docker compose exec -T mariadb-replica mysql -u root -psecret -e "
STOP SLAVE;
CHANGE MASTER TO
    MASTER_HOST='mariadb-primary',
    MASTER_USER='replication',
    MASTER_PASSWORD='replication_password',
    MASTER_USE_GTID=slave_pos;
START SLAVE;
" 2>/dev/null || echo "ℹ️  Replication may already be configured"

# Run Laravel migrations
echo -e "${YELLOW}🗃️  Running Laravel migrations...${NC}"
docker compose exec -T app-1 php artisan migrate --force

# Generate app key if not exists
echo -e "${YELLOW}🔑 Checking application key...${NC}"
docker compose exec -T app-1 php artisan key:generate --force 2>/dev/null || true

# Clear and cache config
echo -e "${YELLOW}🧹 Optimizing Laravel...${NC}"
docker compose exec -T app-1 php artisan config:cache
docker compose exec -T app-1 php artisan route:cache

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}✅ Polyglot DB stack is ready!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo "📊 Access Points:"
echo "   🌐 Application:    http://localhost"
echo "   📈 Grafana:        http://localhost:3000 (admin / admin)"
echo "   🔎 Prometheus:     http://localhost:9099"
echo "   🐳 Portainer:      http://localhost:9000"
echo "   🗄️  Adminer:        http://localhost:8081"
echo "   🍃 Mongo Express:  http://localhost:8082 (admin / pass)"
echo "   🔍 MariaDB Primary: localhost:3308"
echo "   🔍 MariaDB Replica: localhost:3309"
echo "   🍃 MongoDB Primary: localhost:27020"
echo "   🔴 Redis Cache:     localhost:6381"
echo "   🔴 Redis Queue:     localhost:6382"
echo ""
echo "Next: seed demo data with"
echo "   docker compose exec app-1 php artisan db:seed"
