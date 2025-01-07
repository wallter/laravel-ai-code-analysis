#!/bin/bash

# List of ports to check and kill processes for
ports=(8000 8001 8002 8003 8004 8005 8006 8007 8008 8009 8010)

for port in "${ports[@]}"; do
    echo "Checking port $port..."
    pids=$(lsof -t -i:"$port")  # Find all PIDs using the port
    if [ -n "$pids" ]; then
        echo "Port $port is in use by PIDs: $pids. Killing processes..."
        for pid in $pids; do
            kill -9 "$pid" && echo "Process $pid killed." || echo "Failed to kill process $pid."
        done
    else
        echo "Port $port is not in use."
    fi
done

echo "Done."