# Image for running the WordPress integration test suite locally via compose.yaml.
# Mirrors what the GitHub Actions workflow provides on its runner.
ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli

# subversion         - bin/install-wp-tests.sh fetches the WP test framework over svn
# default-mysql-client - handy for poking at the database while debugging
# git/unzip          - composer + WordPress core download
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        subversion \
        default-mysql-client \
    && docker-php-ext-install mysqli \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
