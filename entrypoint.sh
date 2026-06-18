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

mysql -e "CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '1234';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;"
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '1234';"
mysql -e "FLUSH PRIVILEGES;"

echo "Starting Apache Web Server..."
exec apache2-foreground
