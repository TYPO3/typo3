#!/usr/bin/env bash

#
# TYPO3 core test runner based on docker and docker-compose.
#

# Function to write a .env file in Build/testing-docker
# This is read by docker-compose and vars defined here are
# used in Build/testing-docker/docker-compose.yml
setUpDockerComposeDotEnv() {
    # Delete possibly existing local .env file if exists
    [ -e .env ] && rm .env
    # Set up a new .env file for docker-compose
    {
        echo "COMPOSE_PROJECT_NAME=local"
        # To prevent access rights of files created by the testing, the docker image later
        # runs with the same user that is currently executing the script. docker-compose can't
        # use $UID directly itself since it is a shell variable and not an env variable, so
        # we have to set it explicitly here.
        echo "HOST_UID=`id -u`"
        # Your local user
        echo "ROOT_DIR=${ROOT_DIR}"
        echo "HOST_USER=${USER}"
        echo "TEST_FILE=${TEST_FILE}"
        echo "PHP_XDEBUG_ON=${PHP_XDEBUG_ON}"
        echo "PHP_XDEBUG_PORT=${PHP_XDEBUG_PORT}"
        echo "DOCKER_PHP_IMAGE=${DOCKER_PHP_IMAGE}"
        echo "EXTRA_TEST_OPTIONS=${EXTRA_TEST_OPTIONS}"
        echo "SCRIPT_VERBOSE=${SCRIPT_VERBOSE}"
        echo "CGLCHECK_DRY_RUN=${CGLCHECK_DRY_RUN}"
        echo "DATABASE_DRIVER=${DATABASE_DRIVER}"
    } > .env
}

# Options -a and -d depend on each other. The function
# validates input combinations and sets defaults.
handleDbmsAndDriverOptions() {
    case ${DBMS} in
        mysql|mariadb)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
        postgres|sqlite)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid option -a ${DATABASE_DRIVER} with -d ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
            fi
            ;;
    esac
}

# Load help text into $HELP
read -r -d '' HELP <<EOF
styleguide test runner. Execute unit test suite and some other details.
Also used by travis-ci for test execution.

Recommended docker version is >=20.10 for xdebug break pointing to work reliably, and
a recent docker-compose (tested >=1.21.2) is needed.

Usage: $0 [options] [file]

No arguments: Run all unit tests with PHP 8.1

Options:
    -s <...>
        Specifies which test suite to run
            - acceptance: backend acceptance tests
            - cgl: cgl test and fix all php files
            - composerUpdate: "composer update", handy if host has no PHP
            - composerValidate: "composer validate"
            - functional: functional tests
            - lint: PHP linting
            - phpstan: phpstan analyze
            - phpstanGenerateBaseline: regenerate phpstan baseline, handy after phpstan updates
            - unit (default): PHP unit tests

    -a <mysqli|pdo_mysql>
        Only with -s acceptance,functional
        Specifies to use another driver, following combinations are available:
            - mysql
                - mysqli (default)
                - pdo_mysql
            - mariadb
                - mysqli (default)
                - pdo_mysql

    -d <mariadb|mysql|postgres|sqlite>
        Only with -s acceptance,functional
        Specifies on which DBMS tests are performed
            - mariadb (default): use mariadb
            - mysql: use mysql
            - postgres: use postgres
            - sqlite: use sqlite (not for -s acceptance)

    -p <8.1>
        Specifies the PHP minor version to be used
            - 8.1 (default): use PHP 8.1

    -e "<phpunit or codeception options>"
        Only with -s acceptance|functional|unit
        Additional options to send to phpunit (unit & functional tests) or codeception (acceptance
        tests). For phpunit, options starting with "--" must be added after options starting with "-".
        Example -e "-v --filter canRetrieveValueWithGP" to enable verbose output AND filter tests
        named "canRetrieveValueWithGP"

    -x
        Only with -s functional|unit|acceptance
        Send information to host instance for test or system under test break points. This is especially
        useful if a local PhpStorm instance is listening on default xdebug port 9003. A different port
        can be selected with -y

    -y <port>
        Send xdebug information to a different port than default 9003 if an IDE like PhpStorm
        is not listening on default port.

    -n
        Only with -s cgl
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
    # Run unit tests using PHP 8.1
    ./Build/Scripts/runTests.sh
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
cd ../testing-docker || exit 1

# Option defaults
if ! command -v realpath &> /dev/null; then
  echo "This script works best with realpath installed" >&2
  ROOT_DIR="${PWD}/../../"
else
  ROOT_DIR=`realpath ${PWD}/../../`
fi
TEST_SUITE="unit"
DBMS="mariadb"
PHP_VERSION="8.1"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
EXTRA_TEST_OPTIONS=""
SCRIPT_VERBOSE=0
CGLCHECK_DRY_RUN=""
DATABASE_DRIVER=""

# Option parsing
# Reset in case getopts has been used previously in the shell
OPTIND=1
# Array for invalid options
INVALID_OPTIONS=();
# Simple option parsing based on getopts (! not getopt)
while getopts ":s:a:d:p:e:xy:nhuv" OPT; do
    case ${OPT} in
        s)
            TEST_SUITE=${OPTARG}
            ;;
        a)
            DATABASE_DRIVER=${OPTARG}
            ;;
        d)
            DBMS=${OPTARG}
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(8.1)$ ]]; then
                INVALID_OPTIONS+=("p ${OPTARG}")
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
        h)
            echo "${HELP}"
            exit 0
            ;;
        n)
            CGLCHECK_DRY_RUN="-n"
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
    echo "${HELP}" >&2
    exit 1
fi

# Move "7.4" to "php74", the latter is the docker container name
DOCKER_PHP_IMAGE=`echo "php${PHP_VERSION}" | sed -e 's/\.//'`

# Set $1 to first mass argument, this is the optional test file or test directory to execute
shift $((OPTIND - 1))
TEST_FILE=${1}
if [ -n "${1}" ]; then
    TEST_FILE="Web/typo3conf/ext/styleguide/${1}"
fi

if [ ${SCRIPT_VERBOSE} -eq 1 ]; then
    set -x
fi

# Suite execution
case ${TEST_SUITE} in
    acceptance)
        handleDbmsAndDriverOptions
        setUpDockerComposeDotEnv
        case ${DBMS} in
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker-compose run acceptance_backend_mysql80
                SUITE_EXIT_CODE=$?
                ;;
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker-compose run acceptance_backend_mariadb10
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker-compose run acceptance_backend_postgres10
                SUITE_EXIT_CODE=$?
                ;;
            *)
                echo "Acceptance tests don't run with DBMS ${DBMS}" >&2
                echo >&2
                echo "call \".Build/Scripts/runTests.sh -h\" to display help and valid options" >&2
                exit 1
        esac
        docker-compose down
        ;;
    cgl)
        # Active dry-run for cgl needs not "-n" but specific options
        if [[ ! -z ${CGLCHECK_DRY_RUN} ]]; then
            CGLCHECK_DRY_RUN="--dry-run --diff"
        fi
        setUpDockerComposeDotEnv
        docker-compose run cgl
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerUpdate)
        setUpDockerComposeDotEnv
        docker-compose run composer_update
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerValidate)
        setUpDockerComposeDotEnv
        docker-compose run composer_validate
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    functional)
        handleDbmsAndDriverOptions
        setUpDockerComposeDotEnv
        case ${DBMS} in
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker-compose run functional_mariadb10
                SUITE_EXIT_CODE=$?
                ;;
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                docker-compose run functional_mysql80
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker-compose run functional_postgres10
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                # sqlite has a tmpfs as .Build/Web/typo3temp/var/tests/functional-sqlite-dbs/
                # Since docker is executed as root (yay!), the path to this dir is owned by
                # root if docker creates it. Thank you, docker. We create the path beforehand
                # to avoid permission issues.
                mkdir -p ${ROOT_DIR}/.Build/Web/typo3temp/var/tests/functional-sqlite-dbs/
                docker-compose run functional_sqlite
                SUITE_EXIT_CODE=$?
                ;;
            *)
                echo "Invalid -d option argument ${DBMS}" >&2
                echo >&2
                echo "${HELP}" >&2
                exit 1
        esac
        docker-compose down
        ;;
    lint)
        setUpDockerComposeDotEnv
        docker-compose run lint
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    phpstan)
        setUpDockerComposeDotEnv
        docker-compose run phpstan
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    phpstanGenerateBaseline)
        setUpDockerComposeDotEnv
        docker-compose run phpstan_generate_baseline
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    unit)
        setUpDockerComposeDotEnv
        docker-compose run unit
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

exit $SUITE_EXIT_CODE
