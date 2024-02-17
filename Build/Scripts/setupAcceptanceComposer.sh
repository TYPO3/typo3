#!/bin/sh

set -e

cd "$(dirname $(realpath $0))/../../"

PROJECT_PATH=${1:-typo3temp/var/tests/acceptance-composer/}
export TYPO3_INSTALL_DB_DRIVER=${2:-${TYPO3_INSTALL_DB_DRIVER:-pdo_sqlite}}
EXTRA_PACKAGES="${3} helhum/typo3-console:^7"

PHP_MAJOR_VERSION=$(php -r "echo PHP_MAJOR_VERSION;")
PHP_MINOR_VERSION=$(php -r "echo PHP_MINOR_VERSION;")
SYSEXT_PATH=public/typo3/sysext/
TYPO3_CONSOLE=vendor/bin/typo3cms

if [ "${PHP_MAJOR_VERSION}" -ge 8 ] && [ "${PHP_MINOR_VERSION}" -ge 1 ]; then
    EXTRA_PACKAGES="${3} helhum/typo3-console:^8 typo3/cms-composer-installers:^4.0@rc"
    SYSEXT_PATH=vendor/typo3/cms-
    TYPO3_CONSOLE=vendor/bin/typo3
fi

mkdir -p "${PROJECT_PATH}"
ln -snf $(echo "${PROJECT_PATH}" | sed -e 's/[^\/][^\/]*/../g' -e 's/\/$//')/typo3/sysext "${PROJECT_PATH}/typo3-sysext"
ln -snf $(echo "${PROJECT_PATH}" | sed -e 's/[^\/][^\/]*/../g' -e 's/\/$//')/Build/tests/packages "${PROJECT_PATH}/packages"
sed 's/..\/..\/typo3\/sysext/typo3-sysext/' Build/composer/composer.dist.json > "${PROJECT_PATH}/composer.json"

cd "${PROJECT_PATH}"
rm -rf composer.lock public/ var/ vendor/

mkdir -p "public/typo3conf/"
cat > "public/typo3conf/AdditionalConfiguration.php" <<\EOF
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
composer require --no-progress --no-interaction --dev typo3tests/dataset-import:@dev ${EXTRA_PACKAGES}


TYPO3_INSTALL_WEB_SERVER_CONFIG=apache \
TYPO3_INSTALL_SITE_NAME="New TYPO3 site" \
TYPO3_INSTALL_ADMIN_USER=admin \
TYPO3_INSTALL_ADMIN_PASSWORD=password \
TYPO3_INSTALL_WEB_SERVER_CONFIG=apache \
${TYPO3_CONSOLE} install:setup --force --no-interaction
# Align with classic-mode where no system maintainers are defined in presets
${TYPO3_CONSOLE} configuration:remove --force SYS/systemMaintainers

# @see typo3/sysext/core/Tests/Acceptance/Support/Extension/BackendCoreEnvironment.php::xmlDatabaseFixtures
vendor/bin/typo3 dataset:import ${SYSEXT_PATH}core/Tests/Acceptance/Fixtures/be_users_composer_mode.xml
vendor/bin/typo3 dataset:import PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/be_groups.xml
vendor/bin/typo3 dataset:import PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/sys_category.xml
vendor/bin/typo3 dataset:import PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/tx_extensionmanager_domain_model_extension.xml
vendor/bin/typo3 dataset:import ${SYSEXT_PATH}core/Tests/Acceptance/Fixtures/pages.xml
vendor/bin/typo3 dataset:import ${SYSEXT_PATH}core/Tests/Acceptance/Fixtures/workspaces.xml

vendor/bin/typo3 styleguide:generate -c -- all

# Create favicon.ico to suppress potential javascript errors in console
# which are caused by calling a non html in the browser, e.g. seo sitemap xml
ln -snf ../${SYSEXT_PATH}backend/Resources/Public/Icons/favicon.ico public/favicon.ico
