#!/bin/bash
set -e

DIR=$(realpath $(dirname "$0"))
PHP_VERSION=$(phpenv version-name)
ROOT=$(realpath "$DIR/../../../../../..")
PHP_FPM_BIN="$HOME/.phpenv/versions/$PHP_VERSION/sbin/php-fpm"
PHP_FPM_CONF="$DIR/php-fpm.conf"

# Start php-fpm
"$PHP_FPM_BIN" --fpm-config "$PHP_FPM_CONF"

# Build nginx config file and start nginx
sed -e "s|{ROOT}|$ROOT|g" < "$DIR/nginx.tpl.conf" > "$ROOT/nginx.conf"
nginx -c "$ROOT/nginx.conf"