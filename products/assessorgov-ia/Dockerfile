FROM php:8.3-apache
RUN a2enmod rewrite && docker-php-ext-install pdo pdo_pgsql pdo_sqlite
COPY public/ /var/www/html/
COPY src/ /var/www/src/
RUN mkdir -p /var/www/data && chown -R www-data:www-data /var/www/data
HEALTHCHECK --interval=30s --timeout=10s --retries=3 CMD curl -fsS http://localhost/health || exit 1
