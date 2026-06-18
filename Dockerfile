FROM php:8.2-apache

# Install system dependencies, Redis, and MariaDB
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    wget \
    gnupg \
    curl \
    libssl-dev \
    procps \
    redis-server \
    mariadb-server \
    libcurl4 \
    && rm -rf /var/lib/apt/lists/*

# Download and install MongoDB community edition binaries (Debian 12 compatible version)
RUN wget https://fastdl.mongodb.org/linux/mongodb-linux-x86_64-debian12-7.0.12.tgz \
    && tar -zxvf mongodb-linux-x86_64-debian12-7.0.12.tgz \
    && mv mongodb-linux-x86_64-debian12-7.0.12/bin/* /usr/local/bin/ \
    && rm -rf mongodb-linux-x86_64-debian12-7.0.12*

# Install PHP extensions
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files to Apache root
COPY . /var/www/html/

# Clean CRLF line endings from entrypoint.sh (in case of Windows git checkout) and make it executable
RUN sed -i 's/\r$//' /var/www/html/entrypoint.sh \
    && chmod +x /var/www/html/entrypoint.sh

# Expose port 80 (Render routes public traffic here)
EXPOSE 80

# Run entrypoint script
ENTRYPOINT ["/var/www/html/entrypoint.sh"]
