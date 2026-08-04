#!/bin/sh

set -e

cd "$(dirname $(realpath $0))/../../"
CORE_ROOT="$(pwd)"

PROJECT_PATH=${1:-typo3temp/var/tests/playwright-classic/}
ACCEPTANCE_TOPIC="${2}"

# A classic mode instance is not built by composer. It is a document root that borrows
# everything shipped from the core checkout - which itself is a classic mode installation,
# so no TYPO3_COMPOSER_MODE constant is defined and TYPO3 picks the typo3conf/typo3temp
# layout - and owns everything the instance writes to.
rm -rf "${PROJECT_PATH}"
mkdir -p \
    "${PROJECT_PATH}/typo3conf/ext" \
    "${PROJECT_PATH}/typo3conf/system" \
    "${PROJECT_PATH}/typo3temp/var/transient" \
    "${PROJECT_PATH}/fileadmin"

ln -snf "${CORE_ROOT}/typo3" "${PROJECT_PATH}/typo3"
ln -snf "${CORE_ROOT}/vendor" "${PROJECT_PATH}/vendor"
ln -snf "${CORE_ROOT}/Build/tests/playwright/fixtures" "${PROJECT_PATH}/playwright-fixtures"
# Copied rather than linked: the entry point resolves vendor/autoload.php relative to its
# own directory, and a link would resolve that back into the core checkout.
cp "${CORE_ROOT}/index.php" "${PROJECT_PATH}/index.php"

# The support extensions the e2e specs rely on are composer packages for the composer mode
# instance and plain typo3conf/ext extensions here. Same directories, different placement.
for PACKAGE_PATH in "${CORE_ROOT}"/Build/tests/packages/*; do
    ln -snf "${PACKAGE_PATH}" "${PROJECT_PATH}/typo3conf/ext/$(basename "${PACKAGE_PATH}")"
done

# A classic instance is its own document root, and /typo3/ is a real directory in it
# (it carries the system extensions). Without the shipped rewrite rules the web server
# serves that directory instead of routing backend requests to the entry point, so the
# backend is simply unreachable.
cp "${CORE_ROOT}/typo3/sysext/install/Resources/Private/FolderStructureTemplateFiles/root-htaccess" "${PROJECT_PATH}/.htaccess"

cat > "${PROJECT_PATH}/typo3conf/system/additional.php" <<\EOF
<?php
$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = true;
// "Temporary Password - 123"
$GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] = '$argon2i$v=19$m=65536,t=16,p=1$c3hCMGVXOHhRd0M3MzhSVw$WPQHpElapKMxsxfSkkXw5YQxGKN+rGmjM8vQv3g79YY';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = true;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = E_ALL;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'] = E_ALL;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] = 'GraphicsMagick';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'mbox';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_mbox_file'] = \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/log/mail.mbox';

// SQLite optimization: enable WAL mode and busy timeout to prevent "database is locked" errors
if (($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driver'] ?? '') === 'pdo_sqlite') {
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driverOptions'] = [
        \PDO::ATTR_TIMEOUT => 120,
    ];
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['initCommands'] =
        'PRAGMA journal_mode = WAL;' . LF .
        'PRAGMA busy_timeout = 120000;' . LF .
        'PRAGMA synchronous = NORMAL;';
}
EOF

# Classic mode takes its active package set from PackageStates.php, and there is no step
# that creates one for a brand new instance: `typo3 setup` already needs the package set to
# boot, so without this it aborts on an undefined "packages" key. Composer mode never hits
# this because the package artifact supplies the set. Every system extension except the
# distribution theme is activated, matching what the composer instance installs, plus the
# support extensions linked in above.
writePackageStates() {
    php -r '
    $root = $argv[1];
    $project = $argv[2];
    $packages = [];
    foreach (glob($root . "/typo3/sysext/*", GLOB_ONLYDIR) as $path) {
        $key = basename($path);
        if ($key === "theme_camino") {
            continue;
        }
        $packages[$key] = ["packagePath" => "typo3/sysext/" . $key . "/"];
    }
    foreach (glob($project . "/typo3conf/ext/*") as $path) {
        $packages[basename($path)] = ["packagePath" => "typo3conf/ext/" . basename($path) . "/"];
    }
    ksort($packages);
    file_put_contents(
        $project . "/typo3conf/PackageStates.php",
        "<?php" . PHP_EOL . "return " . var_export(["packages" => $packages, "version" => 5], true) . ";" . PHP_EOL
    );
    ' "${CORE_ROOT}" "${CORE_ROOT}/${PROJECT_PATH%/}"
}

writePackageStates

# There is no vendor/bin/typo3 for a classic instance. The core binary is used instead, and
# it is told which instance it operates on through the path environment variables - the same
# mechanism functional tests use.
TYPO3_CLI="${CORE_ROOT}/typo3/sysext/core/bin/typo3"
export TYPO3_PATH_ROOT="${CORE_ROOT}/${PROJECT_PATH%/}"
export TYPO3_PATH_APP="${CORE_ROOT}/${PROJECT_PATH%/}"

TYPO3_SERVER_TYPE=apache \
TYPO3_PROJECT_NAME="New TYPO3 site" \
php "${TYPO3_CLI}" setup --force --no-interaction

# `typo3 setup` rewrites PackageStates.php from the system extensions it knows about and
# drops the support extensions again. `extension:activate` cannot put them back - it only
# knows packages that are already registered, and registering them means a package scan
# that only runs when there is no PackageStates at all. Writing the file is both simpler
# and deterministic.
writePackageStates

# Classic mode generates its autoload information from the active package set, and the
# copy written during setup predates the support extensions. Drop the generated state so
# the next boot rebuilds it, otherwise their classes are simply not autoloadable.
rm -rf "${PROJECT_PATH}/typo3temp/var/build" "${PROJECT_PATH}/typo3temp/var/cache"

php "${TYPO3_CLI}" extension:setup

php "${TYPO3_CLI}" dataset:import "${TYPO3_PATH_ROOT}/playwright-fixtures/BackendEnvironment.csv"
php "${TYPO3_CLI}" styleguide:generate -c tca
if [ "${ACCEPTANCE_TOPIC}" = "systemplate" ]; then
    php "${TYPO3_CLI}" styleguide:generate -c frontend-systemplate
else
    php "${TYPO3_CLI}" styleguide:generate -c frontend
fi

# Create favicon.ico to suppress potential javascript errors in console
# which are caused by calling a non html in the browser, e.g. seo sitemap xml
ln -snf "${CORE_ROOT}/typo3/sysext/backend/Resources/Public/Icons/favicon.ico" "${PROJECT_PATH}/favicon.ico"

# Generate a per-instance secret for the playwright helper middleware. Its
# endpoints (e.g. install-tool/enable) require this token in the
# `X-Playwright-Helper-Secret` request header. The Playwright fixture reads the
# same file and adds the header for every helper request. The instance ports
# are reachable from the host, so authenticating these endpoints is mandatory.
php -r 'echo bin2hex(random_bytes(32));' > "${PROJECT_PATH}/typo3temp/var/transient/playwright-helper.secret"
chmod 600 "${PROJECT_PATH}/typo3temp/var/transient/playwright-helper.secret"
