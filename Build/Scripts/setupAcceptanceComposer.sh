#!/bin/sh

set -e

cd "$(dirname $(realpath $0))/../../"

PROJECT_PATH=${1:-typo3temp/var/tests/acceptance-composer/}
export TYPO3_DB_DRIVER=${2:-${TYPO3_DB_DRIVER:-sqlite}}
EXTRA_PACKAGES="${3}"

mkdir -p "${PROJECT_PATH}"
ln -snf $(echo "${PROJECT_PATH}" | sed -e 's/[^\/][^\/]*/../g' -e 's/\/$//')/typo3/sysext "${PROJECT_PATH}/typo3-sysext"
ln -snf $(echo "${PROJECT_PATH}" | sed -e 's/[^\/][^\/]*/../g' -e 's/\/$//')/Build/tests/packages "${PROJECT_PATH}/packages"
sed 's/..\/..\/typo3\/sysext/typo3-sysext/' Build/composer/composer.dist.json > "${PROJECT_PATH}/composer.json"

cd "${PROJECT_PATH}"
rm -rf composer.lock config/ public/ var/ vendor/

mkdir -p "config/system/"
cat > "config/system/additional.php" <<\EOF
<?php
$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = true;
// "temporary password"
$GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] = '$argon2i$v=19$m=65536,t=16,p=1$Rk9Edk1UWTd1MUtVY1Nydg$bJJgiAH3NT66LkvcTsnYbQvFS/ePOw/50rYjhxUk8L8';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = true;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = E_ALL;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'] = E_ALL;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] = 'GraphicsMagick';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'mbox';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file'] = \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/log/mail.mbox';
EOF

# `composer require` will implicitly perform an initial `composer install` since there is no composer.lock
composer require --no-progress --no-interaction --dev typo3tests/dataset-import:@dev typo3/cms-styleguide:^12.0.5 typo3/testing-framework:^8.2.4 ${EXTRA_PACKAGES}


TYPO3_SERVER_TYPE=apache \
TYPO3_PROJECT_NAME="New TYPO3 site" \
vendor/bin/typo3 setup --force --no-interaction

vendor/bin/typo3 dataset:import vendor/typo3/cms-core/Tests/Acceptance/Fixtures/BackendEnvironment.csv
time vendor/bin/typo3 styleguide:generate -c -- all

# Create favicon.ico to suppress potential javascript errors in console
# which are caused by calling a non html in the browser, e.g. seo sitemap xml
ln -snf ../vendor/typo3/cms-backend/Resources/Public/Icons/favicon.ico public/favicon.ico

# @todo: needed for ugly InstallTool tests, that should be replace by a CLI command that properly enables install tool, both in composer and classic mode
mkdir -p var/transient/
