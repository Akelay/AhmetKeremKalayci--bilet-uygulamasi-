FROM php:8.3-apache-bookworm
SHELL ["/bin/bash","-o","pipefail","-c"]

RUN set -eux; \
    # apt indirmeleri takılırsa 3 deneme yap
    for i in {1..3}; do apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates libsqlite3-dev && break || sleep 3; done; \
    docker-php-ext-install -j"$(nproc)" pdo_sqlite opcache; \
    a2enmod rewrite headers expires; \
    rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}/!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-custom.ini
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

RUN mkdir -p /var/www/html/storage/db \
 && chown -R www-data:www-data /var/www/html/storage \
 && find /var/www/html/storage -type d -exec chmod 0775 {} \; \
 && find /var/www/html/storage -type f -exec chmod 0664 {} \;

HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD curl -fsS http://localhost/ || exit 1
