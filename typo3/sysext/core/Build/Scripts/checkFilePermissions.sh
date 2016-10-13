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
IGNORE=()
IGNORE[0]="typo3/cli_dispatch.phpsh"
IGNORE[1]="typo3/sysext/core/Build/Scripts/cglFixMyCommit.sh"
IGNORE[2]="typo3/sysext/core/Build/Scripts/checkFilePermissions.sh"
IGNORE[3]="typo3/sysext/core/Build/Scripts/duplicateExceptionCodeCheck.sh"
IGNORE[4]="typo3/sysext/core/Build/Scripts/splitFunctionalTests.sh"
IGNORE[5]="typo3/sysext/core/Build/Scripts/xlfcheck.sh"
IGNORE[6]="typo3/sysext/core/bin/typo3"

COUNTER=0

for FILE in $(find typo3/ -type f ! -perm 0644); do
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
