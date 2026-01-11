#!/bin/bash

# Define the container name we are looking for
SHARED_DB_CONTAINER="mariadb-root"

# Check if the container is running
if docker ps --format '{{.Names}}' | grep -q "^${SHARED_DB_CONTAINER}$"; then
    echo "Checking system... Found shared MariaDB container '${SHARED_DB_CONTAINER}'."
    echo "Starting WordPress connected to shared database..."
    
    # We specify project name explicitly to avoid conflicts or confusion with folder names
    # Using 'wordpress-restaurant' as project name
    docker compose -p wordpress-restaurant -f docker-compose.shared.yml up -d
else
    echo "Checking system... Shared MariaDB '${SHARED_DB_CONTAINER}' NOT found."
    echo "Starting standalone WordPress with local MariaDB..."
    
    docker compose -p wordpress-restaurant -f docker-compose.local.yml up -d
fi
