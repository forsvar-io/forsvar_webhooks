FROM php:8.1-cli-alpine

# ----------------------------------------------------
# 🔹 Install required system libs
# ----------------------------------------------------
RUN apk add --no-cache \
        curl \
        bash \
        libcurl \
        openssl \
        ca-certificates \
        oniguruma-dev \
        libxml2-dev \
        git \
        unzip

# ----------------------------------------------------
# 🔹 Install PHP extensions
# ----------------------------------------------------
RUN docker-php-ext-install pcntl mysqli pdo_mysql

# ----------------------------------------------------
# 🔹 Install Composer (for Google PubSub SDK)
# ----------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ----------------------------------------------------
# 🔹 Working directory
# ----------------------------------------------------
WORKDIR /app

# ----------------------------------------------------
# 🔹 Copy application files
# ----------------------------------------------------
COPY . /app

# ----------------------------------------------------
# 🔹 Install PHP dependencies (Google Pub/Sub)
# ----------------------------------------------------
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# ----------------------------------------------------
# 🔹 Default command: run the worker
# ----------------------------------------------------
CMD ["tail", "-f", "/dev/null"]
#CMD ["php", "/app/worker.php"]
