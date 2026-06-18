#!/bin/bash
set -e

echo "Starting Redis..."
redis-server --daemonize yes

echo "Starting MongoDB..."
mkdir -p /data/db
mongod --dbpath /data/db --bind_ip 127.0.0.1 --storageEngine ephemeralForTest &

# Give MongoDB a couple of seconds to bind
sleep 2

echo "Starting MariaDB..."
service mariadb start

echo "Initializing MariaDB user..."
until mysqladmin ping >/dev/null 2>&1; do
    echo "Waiting for MariaDB..."
    sleep 1
done

# Run all configuration queries in a single connection session (supports fresh startup & container restarts)
mysql -u root -e "
  CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '1234';
  GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
  ALTER USER 'root'@'localhost' IDENTIFIED BY '1234';
  FLUSH PRIVILEGES;
" 2>/dev/null || mysql -u root -p1234 -e "
  CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '1234';
  GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
  ALTER USER 'root'@'localhost' IDENTIFIED BY '1234';
  FLUSH PRIVILEGES;
" 2>/dev/null || true

echo "Starting Apache Web Server..."
exec apache2-foreground
