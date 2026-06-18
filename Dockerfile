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

# Download and install libssl1.1 (required for MongoDB 4.4 on Debian Bookworm base)
RUN wget -q http://security.debian.org/debian-security/pool/updates/main/o/openssl/libssl1.1_1.1.1w-0+deb11u1_amd64.deb || \
    wget -q http://ftp.debian.org/debian/pool/main/o/openssl/libssl1.1_1.1.1w-0+deb11u1_amd64.deb \
    && dpkg -i libssl1.1_1.1.1w-0+deb11u1_amd64.deb \
    && rm libssl1.1_1.1.1w-0+deb11u1_amd64.deb

# Download and install MongoDB 4.4.31 community edition binaries (AVX instruction NOT required)
RUN wget -q https://fastdl.mongodb.org/linux/mongodb-linux-x86_64-debian10-4.4.31.tgz \
    && tar -zxvf mongodb-linux-x86_64-debian10-4.4.31.tgz \
    && mv mongodb-linux-x86_64-debian10-4.4.31/bin/* /usr/local/bin/ \
    && rm -rf mongodb-linux-x86_64-debian10-4.4.31*

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
