#!/bin/bash

#########################
#
# CGL check latest core commit.
#
# It expects to be run from the core root.
#
# To auto-fix single files, use the php-cs-fixer command directly
# substitute $FILE with a filename
#
##########################

php_no_xdebug () {
    temporaryPath="$(mktemp -t php.XXXX).ini"
    php -i | grep "\.ini" | grep -o -e '\(/[A-Za-z0-9._-]\+\)\+\.ini' | grep -v xdebug | xargs awk 'FNR==1{print ""}1' > "${temporaryPath}"
    php -n -c "${temporaryPath}" "$@"
    RETURN=$?
    rm -f "${temporaryPath}"
    exit $RETURN
}

DRYRUN=""

if [ "$1" = "dryrun" ]
then
    DRYRUN="--dry-run --diff --diff-format udiff"
fi

DETECTED_FILES=`git diff-tree --no-commit-id --name-only -r HEAD | grep '.php$' 2>/dev/null`
if [ -z "${DETECTED_FILES}" ]
then
    echo "No PHP files to check in current commit, all is well."
    exit 0
fi

php_no_xdebug ./bin/php-cs-fixer fix \
    -v ${DRYRUN} \
    --path-mode intersection \
    --config=Build/.php_cs \
    `echo ${DETECTED_FILES} | xargs ls -d 2>/dev/null`

exit $?
