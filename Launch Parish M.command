#!/bin/bash
# Move to the directory where the script is located
cd "$(dirname "$0")"

echo "Starting Parish Management System..."
echo "Do not close this window while using the application."
echo ""

# Start the PHP server in the background
php -S localhost:8000 > /dev/null 2>&1 &

# Store the process ID to kill it later if needed
SERVER_PID=$!

# Wait a moment for the server to start
sleep 2

# Open the application in the default browser
open http://localhost:8000

echo "System is running at http://localhost:8000"
echo ""
echo "Press Ctrl+C to stop the server when finished."

# Keep the script running to keep the server alive
wait $SERVER_PID
