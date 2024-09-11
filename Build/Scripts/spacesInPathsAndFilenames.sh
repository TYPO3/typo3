#!/bin/bash

RESULT=0

TMP=""
QUOTE="'"

function scanForInvalidFilesAndPaths() {
    find . -name "* *" | while read line; do
        echo " - '${line}${QUOTE}'"
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
