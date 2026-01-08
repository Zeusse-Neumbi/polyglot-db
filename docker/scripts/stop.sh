#!/bin/bash

echo "🛑 Stopping TP_BD Enterprise Stack..."

docker compose down

echo "✅ All containers stopped."
echo ""
echo "💡 To also remove volumes (data), run:"
echo "   docker compose down -v"
