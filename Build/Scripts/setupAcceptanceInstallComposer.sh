#!/bin/sh

set -e

cd "$(dirname $(realpath $0))/../../"

PROJECT_PATH=${1:-typo3temp/var/tests/playwright-install-composer/}

mkdir -p "${PROJECT_PATH}"
ln -snf $(echo "${PROJECT_PATH}" | sed -e 's/[^\/][^\/]*/../g' -e 's/\/$//')/typo3/sysext "${PROJECT_PATH}/typo3-sysext"
ln -snf $(echo "${PROJECT_PATH}" | sed -e 's/[^\/][^\/]*/../g' -e 's/\/$//')/Build/tests/packages "${PROJECT_PATH}/packages"
sed 's/..\/..\/typo3\/sysext/typo3-sysext/' Build/composer/composer.dist.json > "${PROJECT_PATH}/composer.json"

cd "${PROJECT_PATH}"
rm -rf composer.lock config/ public/ var/ vendor/

composer remove typo3/theme-camino --no-update
composer update \
    --no-progress \
    --no-interaction \
    --optimize-autoloader \
    --no-dev

touch public/FIRST_INSTALL

# Create favicon.ico to suppress potential javascript errors in console
ln -snf ../vendor/typo3/cms-backend/Resources/Public/Icons/favicon.ico public/favicon.ico
