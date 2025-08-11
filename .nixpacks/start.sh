#!/bin/bash
echo "Starting PHP built-in server..."
echo "Port: $PORT"
echo "Document root: /app"
cd /app
exec php -S 0.0.0.0:$PORT
