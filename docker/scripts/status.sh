#!/bin/bash

echo "🔍 TP_BD Enterprise Stack - Status Check"
echo "=========================================="

# Check container status
echo ""
echo "📦 Container Status:"
docker compose ps

# Check load balancing
echo ""
echo "🔀 Load Balancing Test (run 4 times):"
for i in {1..4}; do
    SERVED_BY=$(curl -sI http://localhost 2>/dev/null | grep -i "x-served-by" | cut -d' ' -f2)
    if [ -n "$SERVED_BY" ]; then
        echo "   Request $i: Served by $SERVED_BY"
    else
        echo "   Request $i: Could not determine server (app may not be ready)"
    fi
done

# Check MariaDB replication
echo ""
echo "🐬 MariaDB Replication Status:"
docker compose exec -T mariadb-replica mysql -u root -psecret -e "SHOW SLAVE STATUS\G" 2>/dev/null | grep -E "(Slave_IO_Running|Slave_SQL_Running|Seconds_Behind_Master)" || echo "   Could not fetch replication status"

# Check MongoDB replica set
echo ""
echo "🍃 MongoDB Replica Set Status:"
docker compose exec -T mongo-primary mongosh --quiet --eval "
const status = rs.status();
status.members.forEach(m => {
    print('   ' + m.name + ': ' + m.stateStr);
});
" 2>/dev/null || echo "   Could not fetch replica set status"

# Check Redis
echo ""
echo "🔴 Redis Status:"
echo "   Cache: $(docker compose exec -T redis-cache redis-cli ping 2>/dev/null || echo 'not responding')"
echo "   Queue: $(docker compose exec -T redis-queue redis-cli ping 2>/dev/null || echo 'not responding')"

echo ""
echo "=========================================="
