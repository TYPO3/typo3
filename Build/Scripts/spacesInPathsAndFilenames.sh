#!/bin/bash

RESULT=0

TMP=""

# Tracked, staged and new files not yet added; ignored folders such as caches of the test containers are left out
function scanForInvalidFilesAndPaths() {
    git ls-files --cached --others --exclude-standard -z | tr '\0' '\n' | grep ' ' | while read -r line; do
        echo " - '${line}'"
    done
}

TMP="$( scanForInvalidFilesAndPaths )"

if [ -n "${TMP}" ]; then
    echo " -----------------------------------------------------------"
    echo "  Files or folders found with spaces. FAILED !!"
    echo " -----------------------------------------------------------"
    echo ""
    echo "${TMP}"
    echo ""
    RESULT=1
else
    echo ""
    echo " -----------------------------------------------------------"
    echo "  No files or folders with spaces found. OK!"
    echo " -----------------------------------------------------------"
    echo ""
fi

exit $RESULT
