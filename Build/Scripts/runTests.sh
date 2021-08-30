#!/usr/bin/env bash

#
# TYPO3 core test runner based on docker and docker-compose.
#

# Function to write a .env file in Build/testing-docker/local
# This is read by docker-compose and vars defined here are
# used in Build/testing-docker/local/docker-compose.yml
setUpDockerComposeDotEnv() {
    # Delete possibly existing local .env file if exists
    [ -e .env ] && rm .env
    # Set up a new .env file for docker-compose
    echo "COMPOSE_PROJECT_NAME=local" >> .env
    # To prevent access rights of files created by the testing, the docker image later
    # runs with the same user that is currently executing the script. docker-compose can't
    # use $UID directly itself since it is a shell variable and not an env variable, so
    # we have to set it explicitly here.
    echo "HOST_UID=`id -u`" >> .env
    # Your local home directory for composer and npm caching
    echo "HOST_HOME=${HOME}" >> .env
    # Your local user
    echo "CORE_ROOT"=${CORE_ROOT} >> .env
    echo "HOST_USER=${USER}" >> .env
    echo "TEST_FILE=${TEST_FILE}" >> .env
    echo "PHP_XDEBUG_ON=${PHP_XDEBUG_ON}" >> .env
    echo "PHP_XDEBUG_PORT=${PHP_XDEBUG_PORT}" >> .env
    echo "DOCKER_PHP_IMAGE=${DOCKER_PHP_IMAGE}" >> .env
    echo "EXTRA_TEST_OPTIONS=${EXTRA_TEST_OPTIONS}" >> .env
    echo "SCRIPT_VERBOSE=${SCRIPT_VERBOSE}" >> .env
    echo "PHPUNIT_RANDOM=${PHPUNIT_RANDOM}" >> .env
    echo "CGLCHECK_DRY_RUN=${CGLCHECK_DRY_RUN}" >> .env
    echo "DATABASE_DRIVER=${DATABASE_DRIVER}" >> .env
    echo "MARIADB_VERSION=${MARIADB_VERSION}" >> .env
    echo "MYSQL_VERSION=${MYSQL_VERSION}" >> .env
    echo "POSTGRES_VERSION=${POSTGRES_VERSION}" >> .env
    echo "PHP_VERSION=${PHP_VERSION}" >> .env
    echo "CHUNKS=${CHUNKS}" >> .env
    echo "THISCHUNK=${THISCHUNK}" >> .env
    echo "PASSWD_PATH=${PASSWD_PATH}" >> .env
}

# Options -a and -d depend on each other. The function
# validates input combinations and sets defaults.
handleDbmsAndDriverOptions() {
    case ${DBMS} in
        mysql|mariadb)
            [ -z ${DATABASE_DRIVER} ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options"  >&2
                exit 1
            fi
            ;;
        mssql)
            [ -z ${DATABASE_DRIVER} ] && DATABASE_DRIVER="sqlsrv"
            if [ "${DATABASE_DRIVER}" != "sqlsrv" ] && [ "${DATABASE_DRIVER}" != "pdo_sqlsrv" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options"  >&2
                exit 1
            fi
            ;;
        postgres|sqlite)
            if ! [ -z ${DATABASE_DRIVER} ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options"  >&2
                exit 1
            fi
            ;;
    esac
}

# Load help text into $HELP
read -r -d '' HELP <<EOF
TYPO3 core test runner. Execute acceptance, unit, functional and other test suites in
a docker based test environment. Handles execution of single test files, sending
xdebug information to a local IDE and more.

Successfully tested with docker version 18.06.1-ce and docker-compose 1.21.2.

Usage: $0 [options] [file]

No arguments: Run all unit tests with PHP 7.4

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
            - checkCsvFixtures: test integrity of functional test csv fixtures
            - checkExceptionCodes: test core for duplicate exception codes
            - checkExtensionScannerRst: test all .rst files referenced by extension scanner exist
            - checkFilePathLength: test core file paths do not exceed maximum length
            - checkGitSubmodule: test core git has no sub modules defined
            - checkGruntClean: Verify "grunt build" is clean. Warning: Executes git commands! Usually used in CI only.
            - checkPermissions: test some core files for correct executable bits
            - checkRst: test .rst files for integrity
            - checkTestMethodsPrefix: check tests methods do not start with "test"
            - composerInstall: "composer install"
            - composerInstallMax: "composer update", with no platform.php config.
            - composerInstallMin: "composer update --prefer-lowest", with platform.php set to PHP version x.x.0.
            - composerValidate: "composer validate"
            - fixCsvFixtures: fix broken functional test csv fixtures
            - functional: functional tests
            - lintPhp: PHP linting
            - lintScss: SCSS linting
            - lintTypescript: TS linting
            - lintHtml: HTML linting
            - listExceptionCodes: list core exception codes in JSON format
            - phpstan: phpstan tests
            - unit (default): PHP unit tests
            - unitDeprecated: deprecated PHP unit tests
            - unitJavascript: JavaScript unit tests
            - unitRandom: PHP unit tests in random order, add -o <number> to use specific seed

    -a <mysqli|pdo_mysql|sqlsrv|pdo_sqlsrv>
        Only with -s functional
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

    -d <mariadb|mysql|mssql|postgres|sqlite>
        Only with -s functional|acceptance|acceptanceInstall
        Specifies on which DBMS tests are performed
            - mariadb (default): use mariadb
            - mysql: use MySQL server
            - mssql: use mssql microsoft sql server
            - postgres: use postgres
            - sqlite: use sqlite

    -i <10.1|10.2|10.3|10.4|10.5>
        Only with -d mariadb
        Specifies on which version of mariadb tests are performed
            - 10.1
            - 10.2
            - 10.3 (default)
            - 10.4
            - 10.5

    -j <5.5|5.6|5.7|8.0>
        Only with -d mysql
        Specifies on which version of mysql tests are performed
            - 5.5 (default)
            - 5.6
            - 5.7
            - 8.0

    -k <9.6|10|11|12|13>
        Only with -d postgres
        Specifies on which version of postgres tests are performed
            - 9.6
            - 10 (default)
            - 11
            - 12
             -13

    -c <chunk/numberOfChunks>
        Only with -s functional|acceptance
        Hack functional or acceptance tests into #numberOfChunks pieces and run tests of #chunk.
        Example -c 3/13

    -p <7.4|8.0>
        Specifies the PHP minor version to be used
            - 7.4 (default): use PHP 7.4
            - 8.0: use PHP 8.0

    -e "<phpunit options>"
        Only with -s functional|unit|unitDeprecated|unitRandom|acceptance
        Additional options to send to phpunit (unit & functional tests) or codeception (acceptance
        tests). For phpunit, options starting with "--" must be added after options starting with "-".
        Example -e "-v --filter canRetrieveValueWithGP" to enable verbose output AND filter tests
        named "canRetrieveValueWithGP"

    -x
        Only with -s functional|unit|unitDeprecated|unitRandom|acceptance|acceptanceInstall
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
        Only with -s cgl|cglGit
        Activate dry-run in CGL check that does not actively change files and only prints broken ones.

    -u
        Update existing typo3/core-testing-*:latest docker images. Maintenance call to docker pull latest
        versions of the main php images. The images are updated once in a while and only the youngest
        ones are supported by core testing. Use this if weird test errors occur. Also removes obsolete
        image versions of typo3/core-testing-*.

    -v
        Enable verbose script output. Shows variables and docker commands.

    -h
        Show this help.

Examples:
    # Run all core unit tests using PHP 7.4
    ./Build/Scripts/runTests.sh
    ./Build/Scripts/runTests.sh -s unit

    # Run all core units tests and enable xdebug (have a PhpStorm listening on port 9003!)
    ./Build/Scripts/runTests.sh -x

    # Run unit tests in phpunit verbose mode with xdebug on PHP 8.0 and filter for test canRetrieveValueWithGP
    ./Build/Scripts/runTests.sh -x -p 8.0 -e "-v --filter canRetrieveValueWithGP"

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

# Test if docker-compose exists, else exit out with error
if ! type "docker-compose" > /dev/null; then
  echo "This script relies on docker and docker-compose. Please install" >&2
  exit 1
fi

# Go to the directory this script is located, so everything else is relative
# to this dir, no matter from where this script is called.
THIS_SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"
cd "$THIS_SCRIPT_DIR" || exit 1

# Go to directory that contains the local docker-compose.yml file
cd ../testing-docker/local || exit 1

# Set core root path by checking whether realpath exists
if ! command -v realpath &> /dev/null; then
  echo "Consider installing realpath for properly resolving symlinks" >&2
  CORE_ROOT="${PWD}/../../../"
else
  CORE_ROOT=`realpath ${PWD}/../../../`
fi

# Option defaults
TEST_SUITE="unit"
DBMS="mariadb"
PHP_VERSION="7.4"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
EXTRA_TEST_OPTIONS=""
SCRIPT_VERBOSE=0
PHPUNIT_RANDOM=""
CGLCHECK_DRY_RUN=""
DATABASE_DRIVER=""
MARIADB_VERSION="10.3"
MYSQL_VERSION="5.5"
POSTGRES_VERSION="10"
CHUNKS=0
THISCHUNK=0
PASSWD_PATH=/etc/passwd

# Option parsing
# Reset in case getopts has been used previously in the shell
OPTIND=1
# Array for invalid options
INVALID_OPTIONS=();
# Simple option parsing based on getopts (! not getopt)
while getopts ":a:s:c:d:i:j:k:p:e:xy:o:nhuv" OPT; do
    case ${OPT} in
        s)
            TEST_SUITE=${OPTARG}
            ;;
        a)
            DATABASE_DRIVER=${OPTARG}
            ;;
        c)
            if ! [[ ${OPTARG} =~ ^([0-9]+\/[0-9]+)$ ]]; then
                INVALID_OPTIONS+=(${OPTARG})
            else
                # Split "2/13" - run chunk 2 of 13 chunks
                THISCHUNK=`echo ${OPTARG} | cut -d '/' -f1`
                CHUNKS=`echo ${OPTARG} | cut -d '/' -f2`
            fi
            ;;
        d)
            DBMS=${OPTARG}
            ;;
        i)
            MARIADB_VERSION=${OPTARG}
            if ! [[ ${MARIADB_VERSION} =~ ^(10.1|10.2|10.3|10.4|10.5)$ ]]; then
                INVALID_OPTIONS+=(${OPTARG})
            fi
            ;;
        j)
            MYSQL_VERSION=${OPTARG}
            if ! [[ ${MYSQL_VERSION} =~ ^(5.5|5.6|5.7|8.0)$ ]]; then
                INVALID_OPTIONS+=(${OPTARG})
            fi
            ;;
        k)
            POSTGRES_VERSION=${OPTARG}
            if ! [[ ${POSTGRES_VERSION} =~ ^(9.6|10|11|12|13)$ ]]; then
                INVALID_OPTIONS+=(${OPTARG})
            fi
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(7.4|8.0)$ ]]; then
                INVALID_OPTIONS+=(${OPTARG})
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
            echo "${HELP}"
            exit 0
            ;;
        u)
            TEST_SUITE=update
            ;;
        v)
            SCRIPT_VERBOSE=1
            ;;
        \?)
            INVALID_OPTIONS+=(${OPTARG})
            ;;
        :)
            INVALID_OPTIONS+=(${OPTARG})
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

# Move "7.4" to "php74", the latter is the docker container name
DOCKER_PHP_IMAGE=`echo "php${PHP_VERSION}" | sed -e 's/\.//'`

# Some scripts rely on a proper /etc/passwd that includes the user that runs the
# containers, for instance to determine users $HOME. yarn v1 is espcecially picky
# here since it fails if it can't write a .yarnrc file to users home ...
# MacOS in it's endless wisdom however decided that /etc/passwd is a stupid thing
# and does not write an entry for the standard user in it. In turn, stuff like yarn fails.
# As a solution, we detect if the user executing the script is within /etc/passwd
# and volume mount that file within containers. If not, we create a fake passwd file
# and mount that one.
[ -z ${USER} ] && USER=`id -u -n`
if [ `grep -c "^${USER}:" /etc/passwd` -ne 1 ]; then
    echo "${USER}:x:$(id -u $USER):$(id -g $USER):$(id -gn $USER):${HOME}:/bin/bash" > macos_passwd
    PASSWD_PATH="./macos_passwd"
fi

# Set $1 to first mass argument, this is the optional test file or test directory to execute
shift $((OPTIND - 1))
TEST_FILE=${1}

if [ ${SCRIPT_VERBOSE} -eq 1 ]; then
    set -x
fi

# Suite execution
case ${TEST_SUITE} in
    acceptance)
        handleDbmsAndDriverOptions
        setUpDockerComposeDotEnv
        if [ ${CHUNKS} -gt 1 ]; then
            docker-compose run acceptance_split
        fi
        case ${DBMS} in
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker-compose run prepare_acceptance_application_mysql
                docker-compose run acceptance_application_mysql
                SUITE_EXIT_CODE=$?
                ;;
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker-compose run prepare_acceptance_application_mariadb
                docker-compose run acceptance_application_mariadb
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker-compose run prepare_acceptance_application_postgres
                docker-compose run acceptance_application_postgres
                SUITE_EXIT_CODE=$?
                ;;
            *)
                echo "Acceptance tests don't run with DBMS ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options"  >&2
                exit 1
        esac
        docker-compose down
        ;;
    acceptanceInstall)
        handleDbmsAndDriverOptions
        setUpDockerComposeDotEnv
        case ${DBMS} in
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker-compose run prepare_acceptance_install_mysql
                docker-compose run acceptance_install_mysql
                SUITE_EXIT_CODE=$?
                ;;
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker-compose run prepare_acceptance_install_mariadb
                docker-compose run acceptance_install_mariadb
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker-compose run prepare_acceptance_install_postgres
                docker-compose run acceptance_install_postgres
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                docker-compose run prepare_acceptance_install_sqlite
                docker-compose run acceptance_install_sqlite
                SUITE_EXIT_CODE=$?
                ;;
            *)
                echo "Acceptance install tests don't run with DBMS ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options"  >&2
                exit 1
        esac
        docker-compose down
        ;;
    buildCss)
        setUpDockerComposeDotEnv
        docker-compose run build_css
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    buildJavascript)
        setUpDockerComposeDotEnv
        docker-compose run build_javascript
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    cgl)
        # Active dry-run for cgl needs not "-n" but specific options
        if [[ ! -z ${CGLCHECK_DRY_RUN} ]]; then
            CGLCHECK_DRY_RUN="--dry-run --diff"
        fi
        setUpDockerComposeDotEnv
        docker-compose run cgl_all
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    cglGit)
        setUpDockerComposeDotEnv
        docker-compose run cgl_git
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkAnnotations)
        setUpDockerComposeDotEnv
        docker-compose run check_annotations
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkTestMethodsPrefix)
        setUpDockerComposeDotEnv
        docker-compose run check_test_methods_prefix
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkBom)
        setUpDockerComposeDotEnv
        docker-compose run check_bom
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkComposer)
        setUpDockerComposeDotEnv
        docker-compose run check_composer
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkCsvFixtures)
        setUpDockerComposeDotEnv
        docker-compose run check_csv_fixtures
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkExceptionCodes)
        setUpDockerComposeDotEnv
        docker-compose run check_exception_codes
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkExtensionScannerRst)
        setUpDockerComposeDotEnv
        docker-compose run check_extension_scanner_rst
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkFilePathLength)
        setUpDockerComposeDotEnv
        docker-compose run check_file_path_length
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkGitSubmodule)
        setUpDockerComposeDotEnv
        docker-compose run check_git_submodule
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkGruntClean)
        setUpDockerComposeDotEnv
        docker-compose run check_grunt_clean
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkPermissions)
        setUpDockerComposeDotEnv
        docker-compose run check_permissions
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    checkRst)
        setUpDockerComposeDotEnv
        docker-compose run check_rst
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerInstall)
        setUpDockerComposeDotEnv
        docker-compose run composer_install
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerInstallMax)
        setUpDockerComposeDotEnv
        docker-compose run composer_install_max
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerInstallMin)
        setUpDockerComposeDotEnv
        docker-compose run composer_install_min
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerValidate)
        setUpDockerComposeDotEnv
        docker-compose run composer_validate
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    fixCsvFixtures)
        setUpDockerComposeDotEnv
        docker-compose run fix_csv_fixtures
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    functional)
        handleDbmsAndDriverOptions
        setUpDockerComposeDotEnv
        if [ ${CHUNKS} -gt 1 ]; then
            docker-compose run functional_split
        fi
        case ${DBMS} in
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker-compose run prepare_functional_mariadb
                docker-compose run functional_mariadb
                SUITE_EXIT_CODE=$?
                ;;
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker-compose run prepare_functional_mysql
                docker-compose run functional_mysql
                SUITE_EXIT_CODE=$?
                ;;
            mssql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker-compose run prepare_functional_mssql2019latest
                docker-compose run functional_mssql2019latest
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker-compose run prepare_functional_postgres
                docker-compose run functional_postgres
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                # sqlite has a tmpfs as typo3temp/var/tests/functional-sqlite-dbs/
                # Since docker is executed as root (yay!), the path to this dir is owned by
                # root if docker creates it. Thank you, docker. We create the path beforehand
                # to avoid permission issues on host filesystem after execution.
                mkdir -p ${CORE_ROOT}/typo3temp/var/tests/functional-sqlite-dbs/
                docker-compose run prepare_functional_sqlite
                docker-compose run functional_sqlite
                SUITE_EXIT_CODE=$?
                ;;
            *)
                echo "Functional tests don't run with DBMS ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options"  >&2
                exit 1
        esac
        docker-compose down
        ;;
    lintPhp)
        setUpDockerComposeDotEnv
        docker-compose run lint_php
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    lintScss)
        setUpDockerComposeDotEnv
        docker-compose run lint_scss
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    lintTypescript)
        setUpDockerComposeDotEnv
        docker-compose run lint_typescript
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    lintHtml)
        setUpDockerComposeDotEnv
        docker-compose run lint_html
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    listExceptionCodes)
        setUpDockerComposeDotEnv
        docker-compose run list_exception_codes
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    phpstan)
        setUpDockerComposeDotEnv
        docker-compose run phpstan
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    unit)
        setUpDockerComposeDotEnv
        docker-compose run unit
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    unitDeprecated)
        setUpDockerComposeDotEnv
        docker-compose run unitDeprecated
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    unitJavascript)
        setUpDockerComposeDotEnv
        docker-compose run unitJavascript
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    unitRandom)
        setUpDockerComposeDotEnv
        docker-compose run unitRandom
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    update)
        # pull typo3/core-testing-*:latest versions of those ones that exist locally
        docker images typo3/core-testing-*:latest --format "{{.Repository}}:latest" | xargs -I {} docker pull {}
        # remove "dangling" typo3/core-testing-* images (those tagged as <none>)
        docker images typo3/core-testing-* --filter "dangling=true" --format "{{.ID}}" | xargs -I {} docker rmi {}
        ;;
    *)
        echo "Invalid -s option argument ${TEST_SUITE}" >&2
        echo >&2
        echo "${HELP}" >&2
        exit 1
esac

case ${DBMS} in
    mariadb)
        DBMS_OUTPUT="DBMS: ${DBMS}  version ${MARIADB_VERSION}  driver ${DATABASE_DRIVER}"
        ;;
    mysql)
        DBMS_OUTPUT="DBMS: ${DBMS}  version ${MYSQL_VERSION}  driver ${DATABASE_DRIVER}"
        ;;
    mssql)
        DBMS_OUTPUT="DBMS: ${DBMS}  driver ${DATABASE_DRIVER}"
        ;;
    postgres)
        DBMS_OUTPUT="DBMS: ${DBMS}  version ${POSTGRES_VERSION}"
        ;;
    sqlite)
        DBMS_OUTPUT="DBMS: ${DBMS}"
        ;;
    *)
        DBMS_OUTPUT="DBMS not recognized: $DBMS"
        exit 1
esac

# Print summary
if [ ${SCRIPT_VERBOSE} -eq 1 ]; then
    # Turn off verbose mode for the script summary
    set +x
fi
echo "" >&2
echo "###########################################################################" >&2
if [[ ${TEST_SUITE} =~ ^(functional|acceptance|acceptanceInstall)$ ]]; then
    echo "Result of ${TEST_SUITE}" >&2
    echo "PHP: ${PHP_VERSION}" >&2
    echo "${DBMS_OUTPUT}" >&2
else
    echo "Result of ${TEST_SUITE}" >&2
    echo "PHP: ${PHP_VERSION}" >&2
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
