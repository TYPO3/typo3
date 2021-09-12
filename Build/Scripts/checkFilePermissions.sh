#!/bin/bash

#########################
#
# Check all files for their file permission.
# An array of files to be excluded is in place.
#
# It expects to be run from the core root.
#
##########################

# Array of files to ignore the file permission check
IGNORE=(
  "typo3/cli_dispatch.phpsh"
  "typo3/sysext/core/bin/typo3"
)

COUNTER=0

# git stores files either 0644 or 0755. To find files with executable bit set, we test for "owner has x"
for FILE in $(find typo3/ -type f -perm /u+x); do
    if ! [[ ${IGNORE[*]} =~ "$FILE" ]]
    then
        echo $FILE
        COUNTER=$((COUNTER+1))
    fi
done

if [ ${COUNTER} -gt 0 ] ; then
    echo "$COUNTER number of files have a wrong file permission set."
    exit 1
fi

exit 0
