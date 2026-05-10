FROM php:8.2-cli-bookworm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    pkg-config \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /opt/app
COPY . /opt/app

RUN set -e && \
    COMPOSER_MEMORY_LIMIT=-1 composer config --no-plugins audit.abandoned ignore && \
    composer config --no-plugins audit.block-insecure false && \
    echo "Installing dependencies..." && \
    composer install --no-dev --no-scripts --no-interaction --prefer-dist --verbose 2>&1 | tail -20 && \
    echo "Dumping autoload..." && \
    composer dump-autoload --optimize --classmap-authoritative --no-dev && \
    echo "Verifying vendor..." && \
    test -f vendor/composer/autoload_real.php || (echo "FATAL: vendor/composer/autoload_real.php NOT FOUND after install!" && ls -la vendor/composer/ && exit 1) && \
    echo "✓ Build ready"

RUN chmod +x /opt/app/docker/prod/entrypoint.sh
ENTRYPOINT ["/opt/app/docker/prod/entrypoint.sh"]
