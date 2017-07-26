#!/bin/bash

#########################
#
# Limit core file+dir length.
# This script finds relative files that exceeds a specific length
# in total. This is a measure against systems that can handle only
# a limited maximum absolute path length - a recurring issue especially
# on windows systems.
#
# Rule is simple: If this script returns with not 0, for instance by
# a bamboo pre-merge test, then shorten the offending path / file
# combination somehow.
#
# This script expects to be run from the core root.
#
##########################

LIMIT=160

RESULT=0
for FILE in $(find typo3/ -type f); do
    LENGTH=`echo ${FILE} |  wc -m`
    if [[ ${LENGTH} -gt ${LIMIT} ]]; then
        echo "Maximum path length ${LIMIT} violated with ${LENGTH} characters for file ${FILE}"
        RESULT=1
    fi
done

exit $RESULT
