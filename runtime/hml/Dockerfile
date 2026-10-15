FROM php:8.3-fpm-alpine
RUN apk add --no-cache icu-dev oniguruma-dev sqlite-dev libxml2-dev unzip git \
    && docker-php-ext-install pdo_sqlite mbstring dom xml intl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts
COPY app ./app
COPY Modules ./Modules
COPY config ./config
COPY bootstrap ./bootstrap
COPY database ./database
COPY runtime/hml ./runtime/hml
RUN composer dump-autoload --no-dev --optimize --no-scripts
ARG SOURCE_REVISION=unknown
RUN printf '%s' "$SOURCE_REVISION" > runtime/hml/REVISION
CMD ["sh", "runtime/hml/start.sh"]
