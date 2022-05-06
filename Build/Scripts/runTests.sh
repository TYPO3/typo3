#!/usr/bin/env bash

#
# TYPO3 core test runner based on docker.
#

waitFor() {
    local HOST=${1}
    local PORT=${2}
    local TESTCOMMAND="
        COUNT=0;
        while ! nc -z ${HOST} ${PORT}; do
            if [ \"\${COUNT}\" -gt 10 ]; then
              echo \"Can not connect to ${HOST} port ${PORT}. Aborting.\";
              exit 1;
            fi;
            sleep 1;
            COUNT=\$((COUNT + 1));
        done;
    "
    docker run ${DOCKER_COMMON_PARAMS} --name wait-for-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" alpine:3.8 /bin/sh -c "${TESTCOMMAND}"
}

cleanUp() {
    ATTACHED_CONTAINERS=$(docker inspect ${NETWORK} --format '{{range $k, $v := .Containers}}{{println $k}}{{end}}')
    for ATTACHED_CONTAINER in ${ATTACHED_CONTAINERS}; do
        docker rm -f ${ATTACHED_CONTAINER} >/dev/null
    done
    docker network rm ${NETWORK} >/dev/null
}

# Options -a and -d depend on each other. The function
# validates input combinations and sets defaults.
handleDbmsAndDriverOptions() {
    case ${DBMS} in
        mariadb)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        mysql)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        mssql)
            [ -z ${DATABASE_DRIVER} ] && DATABASE_DRIVER="sqlsrv"
            if [ "${DATABASE_DRIVER}" != "sqlsrv" ] && [ "${DATABASE_DRIVER}" != "pdo_sqlsrv" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        postgres)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        sqlite)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        *)
            echo "Invalid option -d ${DBMS}" >&2
            echo >&2
            echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
            exit 1
            ;;
    esac
}

cleanBuildFiles() {
    echo -n "Clean builds ... "
    rm -rf \
        Build/JavaScript \
        Build/node_modules
    echo "done"
}

cleanCacheFiles() {
    echo -n "Clean caches ... "
    rm -rf \
        .cache \
        Build/.cache \
        Build/composer/.cache/ \
        .php-cs-fixer.cache
    echo "done"
}

cleanTestFiles() {
    # composer distribution test
    echo -n "Clean composer distribution test ... "
    rm -rf \
        Build/composer/composer.json \
        Build/composer/composer.lock \
        Build/composer/public/index.php \
        Build/composer/public/typo3 \
        Build/composer/public/typo3conf/ext \
        Build/composer/var/ \
        Build/composer/vendor/
    echo "done"

    # test related
    echo -n "Clean test related files ... "
    rm -rf \
        Build/phpunit/FunctionalTests-Job-*.xml \
        typo3/sysext/core/Tests/AcceptanceTests-Job-* \
        typo3/sysext/core/Tests/Acceptance/Support/_generated \
        typo3temp/var/tests/
    echo "done"
}

cleanRenderedDocumentationFiles() {
    echo -n "Clean rendered documentation files ... "
    rm -rf \
        ../../../typo3/sysext/*/Documentation-GENERATED-temp
    echo "done"
}

loadHelp() {
    # Load help text into $HELP
    read -r -d '' HELP <<EOF
TYPO3 core test runner. Execute acceptance, unit, functional and other test suites in
a docker based test environment. Handles execution of single test files, sending
xdebug information to a local IDE and more.

Usage: $0 [options] [file]

Options:
    -s <...>
        Specifies which test suite to run
            - acceptance: main application acceptance tests
            - acceptanceInstall: installation acceptance tests, only with -d mariadb|postgres|sqlite
            - buildCss: execute scss to css builder
            - buildJavascript: execute typescript to javascript builder
            - cgl: test and fix all core php files
            - cglGit: test and fix latest committed patch for CGL compliance
            - checkAnnotations: check php code for allowed annotations
            - checkBom: check UTF-8 files do not contain BOM
            - checkComposer: check composer.json files for version integrity
            - checkExceptionCodes: test core for duplicate exception codes
            - checkExtensionScannerRst: test all .rst files referenced by extension scanner exist
            - checkFilePathLength: test core file paths do not exceed maximum length
            - checkGitSubmodule: test core git has no sub modules defined
            - checkGruntClean: Verify "grunt build" is clean. Warning: Executes git commands! Usually used in CI only.
            - checkNamespaceIntegrity: Verify namespace integrity in class and test code files are in good shape.
            - checkPermissions: test some core files for correct executable bits
            - checkRst: test .rst files for integrity
            - checkTestMethodsPrefix: check tests methods do not start with "test"
            - clean: clean up build, cache and testing related files and folders
            - cleanBuild: clean up build related files and folders
            - cleanCache: clean up cache related files and folders
            - cleanRenderedDocumentation: clean up rendered documentation files and folders (Documentation-GENERATED-temp)
            - cleanTests: clean up test related files and folders
            - composerInstall: "composer install"
            - composerInstallMax: "composer update", with no platform.php config.
            - composerInstallMin: "composer update --prefer-lowest", with platform.php set to PHP version x.x.0.
            - composerTestDistribution: "composer update" in Build/composer to verify core dependencies
            - composerValidate: "composer validate"
            - functional: PHP functional tests
            - functionalDeprecated: deprecated PHP functional tests
            - lintPhp: PHP linting
            - lintScss: SCSS linting
            - lintTypescript: TS linting
            - lintHtml: HTML linting
            - listExceptionCodes: list core exception codes in JSON format
            - phpstan: phpstan tests
            - phpstanGenerateBaseline: regenerate phpstan baseline, handy after phpstan updates
            - unit (default): PHP unit tests
            - unitDeprecated: deprecated PHP unit tests
            - unitJavascript: JavaScript unit tests
            - unitRandom: PHP unit tests in random order, add -o <number> to use specific seed

    -a <mysqli|pdo_mysql|sqlsrv|pdo_sqlsrv>
        Only with -s functional|functionalDeprecated
        Specifies to use another driver, following combinations are available:
            - mysql
                - mysqli (default)
                - pdo_mysql
            - mariadb
                - mysqli (default)
                - pdo_mysql
            - mssql
                - sqlsrv (default)
                - pdo_sqlsrv

    -d <sqlite|mariadb|mysql|postgres|mssql>
        Only with -s functional|functionalDeprecated|acceptance|acceptanceInstall
        Specifies on which DBMS tests are performed
            - sqlite: (default): use sqlite
            - mariadb: use mariadb
            - mysql: use MySQL
            - postgres: use postgres
            - mssql: use mssql

    -i <10.1|10.2|10.3|10.4|10.5|10.6|10.7|10.8|10.9|10.10|10.11>
        Only with -d mariadb
        Specifies on which version of mariadb tests are performed
            - 10.1   short-term, no longer maintained
            - 10.2   short-term, no longer maintained
            - 10.3   short-term, maintained until 2023-05-25 (default)
            - 10.4   short-term, maintained until 2024-06-18
            - 10.5   short-term, maintained until 2025-06-24
            - 10.6   long-term, maintained until 2026-06
            - 10.7   short-term, no longer maintained
            - 10.8   short-term, maintained until 2023-05
            - 10.9   short-term, maintained until 2023-08
            - 10.10  short-term, maintained until 2023-11
            - 10.11  long-term, maintained until 2028-02

    -j <5.5|5.6|5.7|8.0>
        Only with -d mysql
        Specifies on which version of mysql tests are performed
            - 5.5   unmaintained since 2018-12 (default)
            - 5.6   unmaintained since 2021-02
            - 5.7   maintained until 2023-10
            - 8.0   maintained until 2026-04

    -k <9.6|10|11|12|13|14|15>
        Only with -d postgres
        Specifies on which version of postgres tests are performed
            - 9.6   unmaintained since 2021-11-11
            - 10    unmaintained since 2022-11-10 (default)
            - 11    maintained until 2023-11-09
            - 12    maintained until 2024-11-14
            - 13    maintained until 2025-11-13
            - 14    maintained until 2026-11-12
            - 15    maintained until 2027-11-11

    -c <chunk/numberOfChunks>
        Only with -s functional|acceptance
        Hack functional or acceptance tests into #numberOfChunks pieces and run tests of #chunk.
        Example -c 3/13

    -p <7.4|8.0|8.1|8.2|8.3>
        Specifies the PHP minor version to be used
            - 7.4: (default) use PHP 7.4
            - 8.0: use PHP 8.0
            - 8.1: use PHP 8.1
            - 8.2: use PHP 8.2
            - 8.3: use PHP 8.3

    -e "<phpunit options>"
        Only with -s functional|functionalDeprecated|unit|unitDeprecated|unitRandom|acceptance
        Additional options to send to phpunit (unit & functional tests) or codeception (acceptance
        tests). For phpunit, options starting with "--" must be added after options starting with "-".
        Example -e "-v --filter canRetrieveValueWithGP" to enable verbose output AND filter tests
        named "canRetrieveValueWithGP"

    -x
        Only with -s functional|functionalDeprecated|unit|unitDeprecated|unitRandom|acceptance|acceptanceInstall
        Send information to host instance for test or system under test break points. This is especially
        useful if a local PhpStorm instance is listening on default xdebug port 9003. A different port
        can be selected with -y

    -y <port>
        Send xdebug information to a different port than default 9003 if an IDE like PhpStorm
        is not listening on default port.

    -o <number>
        Only with -s unitRandom
        Set specific random seed to replay a random run in this order again. The phpunit randomizer
        outputs the used seed at the end (in gitlab core testing logs, too). Use that number to
        replay the unit tests in that order.

    -n
        Only with -s cgl|cglGit|cglHeader|cglHeaderGit
        Activate dry-run in CGL check that does not actively change files and only prints broken ones.

    -u
        Update existing typo3/core-testing-*:latest docker images and remove dangling local docker volumes.
        Maintenance call to docker pull latest versions of the main php images. The images are updated once
        in a while and only the latest ones are supported by core testing. Use this if weird test errors occur.
        Also removes obsolete image versions of typo3/core-testing-*.

    -h
        Show this help.

Examples:
    # Run all core unit tests using PHP 7.4
    ./Build/Scripts/runTests.sh
    ./Build/Scripts/runTests.sh -s unit

    # Run all core units tests and enable xdebug (have a PhpStorm listening on port 9003!)
    ./Build/Scripts/runTests.sh -x

    # Run unit tests in phpunit verbose mode with xdebug on PHP 8.0 and filter for test canRetrieveValueWithGP
    ./Build/Scripts/runTests.sh -x -p 8.0 -e "--filter canRetrieveValueWithGP"

    # Run functional tests in phpunit with a filtered test method name in a specified file
    # example will currently execute two tests, both of which start with the search term
    ./Build/Scripts/runTests.sh -s functional -e "--filter deleteContent" typo3/sysext/core/Tests/Functional/DataHandling/Regular/Modify/ActionTest.php

    # Run unit tests with PHP 8.0 and have xdebug enabled
    ./Build/Scripts/runTests.sh -x -p 8.0

    # Run functional tests on postgres with xdebug, php 8.0 and execute a restricted set of tests
    ./Build/Scripts/runTests.sh -x -p 8.0 -s functional -d postgres typo3/sysext/core/Tests/Functional/Authentication

    # Run functional tests on mariadb 10.5
    ./Build/Scripts/runTests.sh -d mariadb -i 10.5

    # Run functional tests on postgres 11
    ./Build/Scripts/runTests.sh -s functional -d postgres -k 11

    # Run restricted set of application acceptance tests
    ./Build/Scripts/runTests.sh -s acceptance typo3/sysext/core/Tests/Acceptance/Application/Login/BackendLoginCest.php:loginButtonMouseOver

    # Run installer tests of a new instance on sqlite
    ./Build/Scripts/runTests.sh -s acceptanceInstall -d sqlite
EOF
}

# Test if docker exists, else exit out with error
if ! type "docker" >/dev/null; then
    echo "This script relies on docker. Please install" >&2
    exit 1
fi

# Option defaults
TEST_SUITE="unit"
DBMS="sqlite"
PHP_VERSION="7.4"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
EXTRA_TEST_OPTIONS=""
PHPUNIT_RANDOM=""
CGLCHECK_DRY_RUN=""
DATABASE_DRIVER=""
MARIADB_VERSION="10.3"
MYSQL_VERSION="5.5"
POSTGRES_VERSION="10"
CHUNKS=0
THISCHUNK=0

# Option parsing updates above default vars
# Reset in case getopts has been used previously in the shell
OPTIND=1
# Array for invalid options
INVALID_OPTIONS=()
# Simple option parsing based on getopts (! not getopt)
while getopts ":a:s:c:d:i:j:k:p:e:xy:o:nhu" OPT; do
    case ${OPT} in
        s)
            TEST_SUITE=${OPTARG}
            ;;
        a)
            DATABASE_DRIVER=${OPTARG}
            ;;
        c)
            if ! [[ ${OPTARG} =~ ^([0-9]+\/[0-9]+)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            else
                # Split "2/13" - run chunk 2 of 13 chunks
                THISCHUNK=$(echo "${OPTARG}" | cut -d '/' -f1)
                CHUNKS=$(echo "${OPTARG}" | cut -d '/' -f2)
            fi
            ;;
        d)
            DBMS=${OPTARG}
            ;;
        i)
            MARIADB_VERSION=${OPTARG}
            if ! [[ ${MARIADB_VERSION} =~ ^(10.1|10.2|10.3|10.4|10.5|10.6|10.7|10.8|10.9|10.10|10.11)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            ;;
        j)
            MYSQL_VERSION=${OPTARG}
            if ! [[ ${MYSQL_VERSION} =~ ^(5.5|5.6|5.7|8.0)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            ;;
        k)
            POSTGRES_VERSION=${OPTARG}
            if ! [[ ${POSTGRES_VERSION} =~ ^(9.6|10|11|12|13|14|15)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(7.4|8.0|8.1|8.2|8.3)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            ;;
        e)
            EXTRA_TEST_OPTIONS=${OPTARG}
            ;;
        x)
            PHP_XDEBUG_ON=1
            ;;
        y)
            PHP_XDEBUG_PORT=${OPTARG}
            ;;
        o)
            PHPUNIT_RANDOM="--random-order-seed=${OPTARG}"
            ;;
        n)
            CGLCHECK_DRY_RUN="-n"
            ;;
        h)
            loadHelp
            echo "${HELP}"
            exit 0
            ;;
        u)
            TEST_SUITE=update
            ;;
        \?)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
        :)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
    esac
done

# Exit on invalid options
if [ ${#INVALID_OPTIONS[@]} -ne 0 ]; then
    echo "Invalid option(s):" >&2
    for I in "${INVALID_OPTIONS[@]}"; do
        echo "-"${I} >&2
    done
    echo >&2
    echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options"
    exit 1
fi

COMPOSER_ROOT_VERSION="11.5.x-dev"
HOST_UID=$(id -u)
USERSET=""
if [ $(uname) != "Darwin" ]; then
    USERSET="--user $HOST_UID"
fi

# Go to the directory this script is located, so everything else is relative
# to this dir, no matter from where this script is called, then go up two dirs.
THIS_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null && pwd)"
cd "$THIS_SCRIPT_DIR" || exit 1
cd ../../ || exit 1
CORE_ROOT="${PWD}"

# Create .cache dir: composer and various npm jobs need this.
mkdir -p .cache
mkdir -p typo3temp/var/tests

DOCKER_SELENIUM_IMAGE="selenium/standalone-chrome:4.0.0-20211102"
PHPSTAN_CONFIG_FILE="phpstan.local.neon"

IS_CORE_CI=0
IMAGE_PREFIX="ghcr.io/typo3/"
DOCKER_INTERACTIVE="-it --init"
# ENV var "CI" is set by gitlab-ci. We use it here to distinct 'local' and 'CI' environment.
if [ "$CI" == "true" ]; then
    IS_CORE_CI=1
    PHPSTAN_CONFIG_FILE="phpstan.ci.neon"
    # Set to empty to use docker hub. CI only, until image cache issue has been solved in infrastructure.
    IMAGE_PREFIX="typo3/"
    DOCKER_INTERACTIVE=""
fi

# Detect arm64 and use a seleniarm image.
# In a perfect world selenium would have a arm64 integrated, but that is not on the horizon.
# So for the time being we have to use seleniarm image.
ARCH=$(uname -m)
if [ $ARCH = "arm64" ]; then
    DOCKER_SELENIUM_IMAGE="seleniarm/standalone-chromium:4.1.2-20220227"
    echo "Architecture" $ARCH "requires" $DOCKER_SELENIUM_IMAGE "to run acceptance tests."
fi

# Move "7.4" to "php74", the latter is the docker container name
DOCKER_PHP_IMAGE=$(echo "php${PHP_VERSION}" | sed -e 's/\.//')
DOCKER_PHP_IMAGE="${IMAGE_PREFIX}core-testing-${DOCKER_PHP_IMAGE}:latest"

# Set $1 to first mass argument, this is the optional test file or test directory to execute
shift $((OPTIND - 1))
TEST_FILE=${1}

SUFFIX=$(echo $RANDOM)
NETWORK="typo3-core-${SUFFIX}"
docker network create ${NETWORK} >/dev/null

DOCKER_COMMON_PARAMS="${DOCKER_INTERACTIVE} --rm --network $NETWORK --add-host "host.docker.internal:host-gateway" $USERSET -v ${CORE_ROOT}:${CORE_ROOT} -w ${CORE_ROOT}"

if [ ${PHP_XDEBUG_ON} -eq 0 ]; then
    XDEBUG_MODE="-e XDEBUG_MODE=off"
    XDEBUG_CONFIG=" "
else
    XDEBUG_MODE="-e XDEBUG_MODE=debug -e XDEBUG_TRIGGER=foo"
    XDEBUG_CONFIG="client_port=${PHP_XDEBUG_PORT} client_host=host.docker.internal"
fi

# Suite execution
case ${TEST_SUITE} in
    acceptance)
        handleDbmsAndDriverOptions
        if [ "${CHUNKS}" -gt 0 ]; then
            docker run ${DOCKER_COMMON_PARAMS} --name ac-splitter-${SUFFIX} ${DOCKER_PHP_IMAGE} php -dxdebug.mode=off Build/Scripts/splitAcceptanceTests.php -v ${CHUNKS}
            COMMAND="bin/codecept run Application -d -g AcceptanceTests-Job-${THISCHUNK} -c typo3/sysext/core/Tests/codeception.yml ${EXTRA_TEST_OPTIONS} ${TEST_FILE} --xml reports.xml --html reports.html"
        else
            COMMAND="bin/codecept run Application -d -c typo3/sysext/core/Tests/codeception.yml ${EXTRA_TEST_OPTIONS} ${TEST_FILE} --xml reports.xml --html reports.html"
        fi
        docker run -d --name ac-chrome-${SUFFIX} --network ${NETWORK} --network-alias chrome --tmpfs /dev/shm:rw,nosuid,nodev,noexec,relatime ${DOCKER_SELENIUM_IMAGE} >/dev/null
        docker run -d --name ac-web-${SUFFIX} --network ${NETWORK} --network-alias web --add-host "host.docker.internal:host-gateway" $USERSET -v ${CORE_ROOT}:${CORE_ROOT} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${DOCKER_PHP_IMAGE} php -S web:8000 -t ${CORE_ROOT} >/dev/null
        waitFor chrome 4444
        waitFor web 8000
        case ${DBMS} in
            mariadb)
                docker run --name mariadb-ac-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid mariadb:${MARIADB_VERSION} >/dev/null
                waitFor mariadb-ac-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabasePassword=funcp -e typo3DatabaseHost=mariadb-ac-${SUFFIX}"
                docker run ${DOCKER_COMMON_PARAMS} --name ac-mariadb ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                ;;
            mysql)
                docker run --name mysql-ac-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid mysql:${MYSQL_VERSION} >/dev/null
                waitFor mysql-ac-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabasePassword=funcp -e typo3DatabaseHost=mysql-ac-${SUFFIX}"
                docker run ${DOCKER_COMMON_PARAMS} --name ac-mysql ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker run --name postgres-ac-${SUFFIX} --network ${NETWORK} -d -e POSTGRES_PASSWORD=funcp -e POSTGRES_USER=funcu --tmpfs /var/lib/postgresql/data:rw,noexec,nosuid postgres:${POSTGRES_VERSION}-alpine >/dev/null
                waitFor postgres-ac-${SUFFIX} 5432
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_pgsql -e typo3DatabaseName=func_test -e typo3DatabaseUsername=funcu -e typo3DatabasePassword=funcp -e typo3DatabaseHost=postgres-ac-${SUFFIX}"
                docker run ${DOCKER_COMMON_PARAMS} --name ac-postgres ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                mkdir -p "${CORE_ROOT}/typo3temp/var/tests/functional-sqlite-dbs/"
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_sqlite --tmpfs ${CORE_ROOT}/typo3temp/var/tests/functional-sqlite-dbs/:rw,noexec,nosuid,uid=${HOST_UID}"
                docker run ${DOCKER_COMMON_PARAMS} --name ac-sqlite ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
        esac
        ;;
    acceptanceInstall)
        handleDbmsAndDriverOptions
        docker run -d --name ac-istall-chrome-${SUFFIX} --network ${NETWORK} --network-alias chrome --tmpfs /dev/shm:rw,nosuid,nodev,noexec,relatime ${DOCKER_SELENIUM_IMAGE} >/dev/null
        docker run -d --name ac-install-web-${SUFFIX} --network ${NETWORK} --network-alias web --add-host "host.docker.internal:host-gateway" $USERSET -v ${CORE_ROOT}:${CORE_ROOT} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${DOCKER_PHP_IMAGE} php -S web:8000 -t ${CORE_ROOT} >/dev/null
        waitFor chrome 4444
        waitFor web 8000
        case ${DBMS} in
            mariadb)
                docker run --name mariadb-ac-install-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid mariadb:${MARIADB_VERSION} >/dev/null
                waitFor mariadb-ac-install-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3InstallMysqlDatabaseName=func_test -e typo3InstallMysqlDatabaseUsername=root -e typo3InstallMysqlDatabasePassword=funcp -e typo3InstallMysqlDatabaseHost=mariadb-ac-install-${SUFFIX}"
                COMMAND="bin/codecept run Install -d -c typo3/sysext/core/Tests/codeception.yml ${EXTRA_TEST_OPTIONS} --env=mysql --xml reports.xml --html reports.html"
                docker run ${DOCKER_COMMON_PARAMS} --name ac-install-sqlite ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            mysql)
                docker run --name mysql-ac-install-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid mysql:${MYSQL_VERSION} >/dev/null
                waitFor mysql-ac-install-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3InstallMysqlDatabaseName=func_test -e typo3InstallMysqlDatabaseUsername=root -e typo3InstallMysqlDatabasePassword=funcp -e typo3InstallMysqlDatabaseHost=mysql-ac-install-${SUFFIX}"
                COMMAND="bin/codecept run Install -d -c typo3/sysext/core/Tests/codeception.yml ${EXTRA_TEST_OPTIONS} --env=mysql --xml reports.xml --html reports.html"
                docker run ${DOCKER_COMMON_PARAMS} --name ac-install-sqlite ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker run --name postgres-ac-install-${SUFFIX} --network ${NETWORK} -d -e POSTGRES_PASSWORD=funcp -e POSTGRES_USER=funcu --tmpfs /var/lib/postgresql/data:rw,noexec,nosuid postgres:${POSTGRES_VERSION}-alpine >/dev/null
                waitFor postgres-ac-install-${SUFFIX} 5432
                CONTAINERPARAMS="-e typo3InstallPostgresqlDatabasePort=5432 -e typo3InstallPostgresqlDatabaseName=${USER} -e typo3InstallPostgresqlDatabaseHost=postgres-ac-install-${SUFFIX} -e typo3InstallPostgresqlDatabaseUsername=funcu -e typo3InstallPostgresqlDatabasePassword=funcp"
                COMMAND="bin/codecept run Install -d -c typo3/sysext/core/Tests/codeception.yml ${EXTRA_TEST_OPTIONS} --env=postgresql --xml reports.xml --html reports.html"
                docker run ${DOCKER_COMMON_PARAMS} --name ac-install-sqlite ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                mkdir -p "${CORE_ROOT}/typo3temp/var/tests/functional-sqlite-dbs/"
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_sqlite --tmpfs ${CORE_ROOT}/typo3temp/var/tests/functional-sqlite-dbs/:rw,noexec,nosuid,uid=${HOST_UID}"
                COMMAND="bin/codecept run Install -d -c typo3/sysext/core/Tests/codeception.yml ${EXTRA_TEST_OPTIONS} --env=sqlite --xml reports.xml --html reports.html"
                docker run ${DOCKER_COMMON_PARAMS} --name ac-install-sqlite ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
        esac
        ;;
    buildCss)
        COMMAND="cd Build; yarn install || exit 1; node_modules/grunt/bin/grunt css ; cd .."
        docker run ${DOCKER_COMMON_PARAMS} --name build-css-${SUFFIX} -e HOME=${CORE_ROOT}/.cache ${IMAGE_PREFIX}core-testing-js:latest /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    buildJavascript)
        COMMAND="cd Build; yarn install || exit 1; node_modules/grunt/bin/grunt scripts ; cd .."
        docker run ${DOCKER_COMMON_PARAMS} --name build-js-${SUFFIX} -e HOME=${CORE_ROOT}/.cache ${IMAGE_PREFIX}core-testing-js:latest /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    cgl)
        # Active dry-run for cgl needs not "-n" but specific options
        if [ -n "${CGLCHECK_DRY_RUN}" ]; then
            CGLCHECK_DRY_RUN="--dry-run --diff"
        fi
        COMMAND="php -dxdebug.mode=off bin/php-cs-fixer fix -v ${CGLCHECK_DRY_RUN} --path-mode intersection --config=Build/php-cs-fixer/config.php typo3/"
        docker run ${DOCKER_COMMON_PARAMS} --name cgl-${SUFFIX} ${DOCKER_PHP_IMAGE} ${COMMAND}
        SUITE_EXIT_CODE=$?
        ;;
    cglGit)
        docker run ${DOCKER_COMMON_PARAMS} --name cgl-git-${SUFFIX} ${DOCKER_PHP_IMAGE} Build/Scripts/cglFixMyCommit.sh ${CGLCHECK_DRY_RUN}
        SUITE_EXIT_CODE=$?
        ;;
    checkAnnotations)
        docker run ${DOCKER_COMMON_PARAMS} --name check-annotations-${SUFFIX} ${DOCKER_PHP_IMAGE} php -dxdebug.mode=off Build/Scripts/annotationChecker.php
        SUITE_EXIT_CODE=$?
        ;;
    checkTestClassFinal)
        docker run ${DOCKER_COMMON_PARAMS} --name check-test-classes-final-${SUFFIX} ${DOCKER_PHP_IMAGE} php -dxdebug.mode=off Build/Scripts/testClassFinalChecker.php
        SUITE_EXIT_CODE=$?
        ;;
    checkTestMethodsPrefix)
        docker run ${DOCKER_COMMON_PARAMS} --name check-test-methods-prefix-${SUFFIX} ${DOCKER_PHP_IMAGE} php -dxdebug.mode=off Build/Scripts/testMethodPrefixChecker.php
        SUITE_EXIT_CODE=$?
        ;;
    checkBom)
        docker run ${DOCKER_COMMON_PARAMS} --name check-utf8bom-${SUFFIX} ${DOCKER_PHP_IMAGE} Build/Scripts/checkUtf8Bom.sh
        SUITE_EXIT_CODE=$?
        ;;
    checkComposer)
        docker run ${DOCKER_COMMON_PARAMS} --name check-composer-${SUFFIX} ${DOCKER_PHP_IMAGE} php -dxdebug.mode=off Build/Scripts/checkIntegrityComposer.php
        SUITE_EXIT_CODE=$?
        ;;
    checkExceptionCodes)
        docker run ${DOCKER_COMMON_PARAMS} --name check-exception-codes-${SUFFIX} ${DOCKER_PHP_IMAGE} Build/Scripts/duplicateExceptionCodeCheck.sh
        SUITE_EXIT_CODE=$?
        ;;
    checkExtensionScannerRst)
        docker run ${DOCKER_COMMON_PARAMS} --name check-extensionscanner-rst-${SUFFIX} ${DOCKER_PHP_IMAGE} php -dxdebug.mode=off Build/Scripts/extensionScannerRstFileReferences.php
        SUITE_EXIT_CODE=$?
        ;;
    checkFilePathLength)
        docker run ${DOCKER_COMMON_PARAMS} --name check-file-path-length-${SUFFIX} ${DOCKER_PHP_IMAGE} Build/Scripts/maxFilePathLength.sh
        SUITE_EXIT_CODE=$?
        ;;
    checkGitSubmodule)
        COMMAND="if [ \$(git submodule status 2>&1 | wc -l) -ne 0 ]; then echo \"Found a submodule definition in repository\"; exit 1; fi"
        docker run ${DOCKER_COMMON_PARAMS} --name check-git-submodule-${SUFFIX} ${DOCKER_PHP_IMAGE} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    checkGruntClean)
        COMMAND="cd Build; yarn install || exit 1; node_modules/grunt/bin/grunt build; cd ..; git add *; git status; git status | grep -q \"nothing to commit, working tree clean\""
        docker run ${DOCKER_COMMON_PARAMS} --name check-grunt-clean-${SUFFIX} -e HOME=${CORE_ROOT}/.cache ${IMAGE_PREFIX}core-testing-js:latest /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    checkNamespaceIntegrity)
        docker run ${DOCKER_COMMON_PARAMS} --name check-namespaces-${SUFFIX} ${DOCKER_PHP_IMAGE} php -dxdebug.mode=off Build/Scripts/checkNamespaceIntegrity.php
        SUITE_EXIT_CODE=$?
        ;;
    checkPermissions)
        docker run ${DOCKER_COMMON_PARAMS} --name check-permissions-${SUFFIX} ${DOCKER_PHP_IMAGE} Build/Scripts/checkFilePermissions.sh
        SUITE_EXIT_CODE=$?
        ;;
    checkRst)
        docker run ${DOCKER_COMMON_PARAMS} --name check-rst-${SUFFIX} ${DOCKER_PHP_IMAGE} php -dxdebug.mode=off Build/Scripts/validateRstFiles.php
        SUITE_EXIT_CODE=$?
        ;;
    clean)
        cleanBuildFiles
        cleanCacheFiles
        cleanRenderedDocumentationFiles
        cleanTestFiles
        ;;
    cleanBuild)
        cleanBuildFiles
        ;;
    cleanCache)
        cleanCacheFiles
        ;;
    cleanRenderedDocumentation)
        cleanRenderedDocumentationFiles
        ;;
    cleanTests)
        cleanTestFiles
        ;;
    composerInstall)
        docker run ${DOCKER_COMMON_PARAMS} --name composer-install-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${DOCKER_PHP_IMAGE} composer install --no-progress --no-interaction
        SUITE_EXIT_CODE=$?
        ;;
    composerInstallMax)
        COMMAND="composer config --unset platform.php; composer update --no-progress --no-interaction; composer dumpautoload"
        docker run ${DOCKER_COMMON_PARAMS} --name composer-install-max-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${DOCKER_PHP_IMAGE} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    composerInstallMin)
        COMMAND="composer config platform.php ${PHP_VERSION}.0; composer update --prefer-lowest --no-progress --no-interaction; composer dumpautoload"
        docker run ${DOCKER_COMMON_PARAMS} --name composer-install-min-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${DOCKER_PHP_IMAGE} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    composerTestDistribution)
        COMMAND="cd Build/composer; rm -rf composer.json composer.lock public/index.php public/typo3 public/typo3conf/ext var/ vendor/; cp composer.dist.json composer.json; composer update --no-progress --no-interaction"
        docker run ${DOCKER_COMMON_PARAMS} --name composer-test-distribution-${SUFFIX} -e COMPOSER_CACHE_DIR=${CORE_ROOT}/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${DOCKER_PHP_IMAGE} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    composerValidate)
        docker run ${DOCKER_COMMON_PARAMS} --name composer-validate-${SUFFIX} ${DOCKER_PHP_IMAGE} composer validate
        SUITE_EXIT_CODE=$?
        ;;
    functional)
        handleDbmsAndDriverOptions
        if [ "${CHUNKS}" -gt 0 ]; then
            docker run ${DOCKER_COMMON_PARAMS} --name func-splitter-${SUFFIX} ${DOCKER_PHP_IMAGE} php -dxdebug.mode=off Build/Scripts/splitFunctionalTests.php -v ${CHUNKS}
            COMMAND="bin/phpunit -c Build/phpunit/FunctionalTests-Job-${THISCHUNK}.xml --exclude-group not-${DBMS} ${EXTRA_TEST_OPTIONS} ${TEST_FILE}"
        else
            COMMAND="bin/phpunit -c Build/phpunit/FunctionalTests.xml --exclude-group not-${DBMS} ${EXTRA_TEST_OPTIONS} ${TEST_FILE}"
        fi
        docker run --name redis-func-${SUFFIX} --network ${NETWORK} -d redis:4-alpine >/dev/null
        docker run --name memcached-func-${SUFFIX} --network ${NETWORK} -d memcached:1.5-alpine >/dev/null
        waitFor redis-func-${SUFFIX} 6379
        waitFor memcached-func-${SUFFIX} 11211
        DOCKER_COMMON_PARAMS="${DOCKER_COMMON_PARAMS} -e typo3TestingRedisHost=redis-func-${SUFFIX} -e typo3TestingMemcachedHost=memcached-func-${SUFFIX}"
        case ${DBMS} in
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker run --name mariadb-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid mariadb:${MARIADB_VERSION} >/dev/null
                waitFor mariadb-func-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mariadb-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                docker run ${DOCKER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker run --name mysql-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid mysql:${MYSQL_VERSION} >/dev/null
                waitFor mysql-func-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mysql-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                docker run ${DOCKER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            mssql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker run --name mssql-func-${SUFFIX} --network ${NETWORK} -d -e ACCEPT_EULA="Y" -e SA_PASSWORD="Test1234!" -e MSSQL_PID=Developer typo3/core-testing-mssql2019:latest >/dev/null
                waitFor mssql-func-${SUFFIX} 1433
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabasePort=1433 -e typo3DatabaseName=func_test -e typo3DatabaseUsername=SA -e typo3DatabaseHost=mssql-func-${SUFFIX} -e typo3DatabasePassword=Test1234! -e typo3DatabaseCharset=utf-8"
                docker run ${DOCKER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker run --name postgres-func-${SUFFIX} --network ${NETWORK} -d -e POSTGRES_PASSWORD=funcp -e POSTGRES_USER=funcu --tmpfs /var/lib/postgresql/data:rw,noexec,nosuid postgres:${POSTGRES_VERSION}-alpine >/dev/null
                waitFor postgres-func-${SUFFIX} 5432
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_pgsql -e typo3DatabaseName=bamboo -e typo3DatabaseUsername=funcu -e typo3DatabaseHost=postgres-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                docker run ${DOCKER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                # create sqlite tmpfs mount typo3temp/var/tests/functional-sqlite-dbs/ to avoid permission issues
                mkdir -p "${CORE_ROOT}/typo3temp/var/tests/functional-sqlite-dbs/"
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_sqlite --tmpfs ${CORE_ROOT}/typo3temp/var/tests/functional-sqlite-dbs/:rw,noexec,nosuid,uid=${HOST_UID}"
                docker run ${DOCKER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
        esac
        ;;
    functionalDeprecated)
        handleDbmsAndDriverOptions
        COMMAND="bin/phpunit -c Build/phpunit/FunctionalTestsDeprecated.xml --exclude-group not-${DBMS} ${EXTRA_TEST_OPTIONS} ${TEST_FILE}"
        docker run --name redis-func-dep-${SUFFIX} --network ${NETWORK} -d redis:4-alpine >/dev/null
        docker run --name memcached-func-dep-${SUFFIX} --network ${NETWORK} -d memcached:1.5-alpine >/dev/null
        waitFor redis-func-dep-${SUFFIX} 6379
        waitFor memcached-func-dep-${SUFFIX} 11211
        DOCKER_COMMON_PARAMS="${DOCKER_COMMON_PARAMS} -e typo3TestingRedisHost=redis-func-dep-${SUFFIX} -e typo3TestingMemcachedHost=memcached-func-dep-${SUFFIX}"
        case ${DBMS} in
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker run --name mariadb-func-dep-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid mariadb:${MARIADB_VERSION} >/dev/null
                waitFor mariadb-func-dep-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mariadb-func-dep-${SUFFIX} -e typo3DatabasePassword=funcp"
                docker run ${DOCKER_COMMON_PARAMS} --name functional-deprecated-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker run --name mysql-func-dep-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid mysql:${MYSQL_VERSION} >/dev/null
                waitFor mysql-func-dep-${SUFFIX} 3306
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mysql-func-dep-${SUFFIX} -e typo3DatabasePassword=funcp"
                docker run ${DOCKER_COMMON_PARAMS} --name functional-deprecated-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            mssql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker run --name mssql-func-dep-${SUFFIX} --network ${NETWORK} -d -e ACCEPT_EULA="Y" -e SA_PASSWORD="Test1234!" -e MSSQL_PID=Developer typo3/core-testing-mssql2019:latest >/dev/null
                waitFor mssql-func-dep-${SUFFIX} 1433
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabasePort=1433 -e typo3DatabaseName=func_test -e typo3DatabaseUsername=SA -e typo3DatabaseHost=mssql-func-dep-${SUFFIX} -e typo3DatabasePassword=Test1234! -e typo3DatabaseCharset=utf-8"
                docker run ${DOCKER_COMMON_PARAMS} --name functional-deprecated-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker run --name postgres-func-dep-${SUFFIX} --network ${NETWORK} -d -e POSTGRES_PASSWORD=funcp -e POSTGRES_USER=funcu --tmpfs /var/lib/postgresql/data:rw,noexec,nosuid postgres:${POSTGRES_VERSION}-alpine >/dev/null
                waitFor postgres-func-dep-${SUFFIX} 5432
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_pgsql -e typo3DatabaseName=bamboo -e typo3DatabaseUsername=funcu -e typo3DatabaseHost=postgres-func-dep-${SUFFIX} -e typo3DatabasePassword=funcp"
                docker run ${DOCKER_COMMON_PARAMS} --name functional-deprecated-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                # create sqlite tmpfs mount typo3temp/var/tests/functional-sqlite-dbs/ to avoid permission issues
                mkdir -p "${CORE_ROOT}/typo3temp/var/tests/functional-sqlite-dbs/"
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_sqlite --tmpfs ${CORE_ROOT}/typo3temp/var/tests/functional-sqlite-dbs/:rw,noexec,nosuid,uid=${HOST_UID}"
                docker run ${DOCKER_COMMON_PARAMS} --name functional-deprecated-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${DOCKER_PHP_IMAGE} ${COMMAND}
                SUITE_EXIT_CODE=$?
                ;;
        esac
        ;;
    lintPhp)
        COMMAND="php -v | grep '^PHP'; find typo3/ -name \\*.php -print0 | xargs -0 -n1 -P4 php -dxdebug.mode=off -l >/dev/null"
        docker run ${DOCKER_COMMON_PARAMS} --name lint-php-${SUFFIX} ${DOCKER_PHP_IMAGE} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    lintScss)
        COMMAND="cd Build; yarn install || exit 1; node_modules/grunt/bin/grunt stylelint"
        docker run ${DOCKER_COMMON_PARAMS} --name lint-css-${SUFFIX} -e HOME=${CORE_ROOT}/.cache ${IMAGE_PREFIX}core-testing-js:latest /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    lintTypescript)
        COMMAND="cd Build; yarn install || exit 1; node_modules/grunt/bin/grunt eslint"
        docker run ${DOCKER_COMMON_PARAMS} --name lint-typescript-${SUFFIX} -e HOME=${CORE_ROOT}/.cache ${IMAGE_PREFIX}core-testing-js:latest /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    lintHtml)
        COMMAND="cd Build; yarn install || exit 1; node_modules/grunt/bin/grunt lintspaces"
        docker run ${DOCKER_COMMON_PARAMS} --name lint-html-${SUFFIX} -e HOME=${CORE_ROOT}/.cache ${IMAGE_PREFIX}core-testing-js:latest /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    listExceptionCodes)
        docker run ${DOCKER_COMMON_PARAMS} --name list-exception-codes-${SUFFIX} ${DOCKER_PHP_IMAGE} Build/Scripts/duplicateExceptionCodeCheck.sh -p
        SUITE_EXIT_CODE=$?
        ;;
    phpstan)
        COMMAND="php -dxdebug.mode=off bin/phpstan analyse -c Build/phpstan/${PHPSTAN_CONFIG_FILE} --no-progress --no-interaction --memory-limit 4G ${TEST_FILE}"
        docker run ${DOCKER_COMMON_PARAMS} --name phpstan-${SUFFIX} ${DOCKER_PHP_IMAGE} sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    phpstanGenerateBaseline)
        COMMAND="php -dxdebug.mode=off bin/phpstan analyse -c Build/phpstan/${PHPSTAN_CONFIG_FILE} --no-progress --no-interaction --memory-limit 4G --generate-baseline=Build/phpstan/phpstan-baseline.neon"
        docker run ${DOCKER_COMMON_PARAMS} --name phpstan-baseline-${SUFFIX} ${DOCKER_PHP_IMAGE} sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    unit)
        docker run ${DOCKER_COMMON_PARAMS} --name unit-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${DOCKER_PHP_IMAGE} bin/phpunit -c Build/phpunit/UnitTests.xml ${EXTRA_TEST_OPTIONS} ${TEST_FILE}
        SUITE_EXIT_CODE=$?
        ;;
    unitDeprecated)
        docker run ${DOCKER_COMMON_PARAMS} --name unit-deprecated-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${DOCKER_PHP_IMAGE} bin/phpunit -c Build/phpunit/UnitTestsDeprecated.xml ${EXTRA_TEST_OPTIONS} ${TEST_FILE}
        SUITE_EXIT_CODE=$?
        ;;
    unitJavascript)
        COMMAND="cd Build; yarn install || exit 1; cd ..; Build/node_modules/karma/bin/karma start vendor/typo3/testing-framework/Resources/Core/Build/Configuration/JSUnit/karma.conf.ci.js --single-run"
        docker run ${DOCKER_COMMON_PARAMS} --name unit-javascript-${SUFFIX} -e HOME=${CORE_ROOT}/.cache ${IMAGE_PREFIX}core-testing-js-chrome:latest /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    unitRandom)
        docker run ${DOCKER_COMMON_PARAMS} --name unit-random-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${DOCKER_PHP_IMAGE} bin/phpunit -c Build/phpunit/UnitTests.xml --order-by=random ${EXTRA_TEST_OPTIONS} ${PHPUNIT_RANDOM} ${TEST_FILE}
        SUITE_EXIT_CODE=$?
        ;;
    update)
        # prune unused, dangling local volumes
        echo "> prune unused, dangling local volumes"
        docker volume ls -q -f driver=local -f dangling=true | awk '$0 ~ /^[0-9a-f]{64}$/ { print }' | xargs -I {} docker volume rm {}
        echo ""
        # pull typo3/core-testing-*:latest versions of those ones that exist locally
        echo "> pull ${IMAGE_PREFIX}core-testing-*:latest versions of those ones that exist locally"
        docker images ${IMAGE_PREFIX}core-testing-*:latest --format "{{.Repository}}:latest" | xargs -I {} docker pull {}
        echo ""
        # remove "dangling" typo3/core-testing-* images (those tagged as <none>)
        echo "> remove \"dangling\" ${IMAGE_PREFIX}core-testing-* images (those tagged as <none>)"
        docker images ${IMAGE_PREFIX}core-testing-* --filter "dangling=true" --format "{{.ID}}" | xargs -I {} docker rmi {}
        echo ""
        ;;
    *)
        loadHelp
        echo "Invalid -s option argument ${TEST_SUITE}" >&2
        echo >&2
        echo "${HELP}" >&2
        exit 1
        ;;
esac

cleanUp

# Print summary
echo "" >&2
echo "###########################################################################" >&2
echo "Result of ${TEST_SUITE}" >&2
if [[ ${IS_CORE_CI} -eq 1 ]]; then
    echo "Environment: CI" >&2
else
    echo "Environment: local" >&2
fi
echo "PHP: ${PHP_VERSION}" >&2
if [[ ${TEST_SUITE} =~ ^(functional|functionalDeprecated|acceptance|acceptanceInstall)$ ]]; then
    case "${DBMS}" in
        mariadb)
            echo "DBMS: ${DBMS}  version ${MARIADB_VERSION}  driver ${DATABASE_DRIVER}" >&2
            ;;
        mysql)
            echo "DBMS: ${DBMS}  version ${MYSQL_VERSION}  driver ${DATABASE_DRIVER}" >&2
            ;;
        mssql)
            echo "DBMS: ${DBMS}  driver ${DATABASE_DRIVER}" >&2
            ;;
        postgres)
            echo "DBMS: ${DBMS}  version ${POSTGRES_VERSION}" >&2
            ;;
        sqlite)
            echo "DBMS: ${DBMS}" >&2
            ;;
    esac
fi
if [[ ${SUITE_EXIT_CODE} -eq 0 ]]; then
    echo "SUCCESS" >&2
else
    echo "FAILURE" >&2
fi
echo "###########################################################################" >&2
echo "" >&2

# Exit with code of test suite - This script return non-zero if the executed test failed.
exit $SUITE_EXIT_CODE
