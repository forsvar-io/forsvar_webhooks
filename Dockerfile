FROM php:8.1-fpm-alpine

# ----------------------------------------------------
# 🔹 Install required system libs + PHP build deps
# ----------------------------------------------------
RUN apk add --no-cache \
        curl \
        bash \
        libcurl \
        mariadb-connector-c \
        mariadb-dev \
        openssl \
        ca-certificates \
        oniguruma-dev \
        libxml2-dev \
        autoconf \
        make \
        g++ 

# ----------------------------------------------------
# 🔹 Install PHP extensions
# ----------------------------------------------------
RUN docker-php-ext-install mysqli pdo_mysql

# ----------------------------------------------------
# 🔹 Create workdir
# ----------------------------------------------------
WORKDIR /app
COPY . /app

# ----------------------------------------------------
# 🔹 Cloud Run expects app listening on $PORT
# ----------------------------------------------------
ENV PORT=8088

EXPOSE 8088

# ----------------------------------------------------
# 🔹 Run PHP built-in web server (recommended for Cloud Run)
# ----------------------------------------------------
CMD ["php", "-S", "0.0.0.0:8088", "index.php"]
