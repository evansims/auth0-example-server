FROM php:7.4-fpm-alpine

# Set working directory for our project
WORKDIR /var/www

# Update apk; this can resolve some build errors.
RUN apk update

# Install essential build tools
RUN apk add \
    zip \
    unzip \
    libzip-dev \
    git \
    autoconf \
    g++ \
    openssl-dev

# Set UTC as container time
RUN echo "UTC" > /etc/timezone

# Install composer for installing project dependencies
ENV COMPOSER_HOME /composer
ENV PATH ./vendor/bin:/composer/vendor/bin:$PATH
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN curl -s https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

# Point PEAR toward the propr PHP ini
RUN pear config-set php_ini "$PHP_INI_DIR"

# Setup bzip2 extension  --no-cache
RUN apk add \
    bzip2-dev \
    && docker-php-ext-install -j$(nproc) bz2 \
    && docker-php-ext-enable bz2 \
    && rm -rf /tmp/*

# Setup GD extension
RUN apk add \
      freetype \
      libjpeg-turbo \
      libpng \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
    && docker-php-ext-configure gd \
      --with-freetype=/usr/include/ \
      --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd \
    && apk del \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
    && rm -rf /tmp/*

# Install intl extension
RUN apk add \
    icu-dev \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-enable intl \
    && rm -rf /tmp/*

# Install mbstring extension
RUN apk add \
    oniguruma-dev \
    && docker-php-ext-install mbstring \
    && docker-php-ext-enable mbstring \
    && rm -rf /tmp/*

# Install additional extensions
RUN apk add \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-enable pdo_mysql mbstring zip exif pcntl \
    && rm -rf /tmp/*

# Create system user to run Composer and Artisan Commands
# RUN groupadd -g 1000 www-data
# RUN useradd -u 1000 -ms /bin/bash -g www-data www-data

# USER www-data

CMD ["php-fpm"]
