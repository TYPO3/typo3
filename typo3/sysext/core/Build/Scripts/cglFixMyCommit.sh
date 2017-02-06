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

COUNTER=0
DRYRUN=""

if [ "$1" = "dryrun" ]
then
    DRYRUN="--dry-run"
fi

for FILE in $(git diff-tree --no-commit-id --name-only -r HEAD | grep '.php$'); do
    if [ -e $FILE ]
    then
        ./bin/php-cs-fixer fix $FILE \
            -v $DRYRUN \
            --config=Build/.php_cs

        if [ "$?" -gt "0" ]
        then
            COUNTER=$((COUNTER+1))
        fi
    fi
done

if [ ${COUNTER} -gt 0 ] ; then
    echo "$COUNTER number of files are not CGL clean. Check $0 to find out what is going wrong."
    exit 1
fi

exit 0
