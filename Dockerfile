FROM php:7.4-apache
COPY . /var/www/html
RUN DEBIAN_FRONTEND=noninteractive apt-get update && apt-get install -y wget gnupg iputils-ping iproute2 curl
RUN DEBIAN_FRONTEND=noninteractive apt-get install -y \
    debian-archive-keyring \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libmagickwand-dev libmagickcore-dev imagemagick \
    lsb-release \
    libgmp-dev \
    zlib1g-dev \
    libgeoip-dev \
    expect-dev \
    git \
    nano \
    python \
    locales \
    libmcrypt-dev \
    libzip-dev \
    && ln -s /usr/include/x86_64-linux-gnu/gmp.h /usr/include/gmp.h \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql bcmath zip gmp soap \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN a2enmod rewrite && \
    service apache2 restart && \
    chown www-data:www-data -R /var/www/html
EXPOSE 80 3306 443