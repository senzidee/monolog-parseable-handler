FROM php:8.1-cli-alpine
LABEL authors="Mario Ravalli"

RUN apk --no-cache add bash patch unzip zip zlib
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
        @composer \
        xdebug
ARG COMPOSER_FLAGS="--no-interaction --no-progress --ansi"
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app