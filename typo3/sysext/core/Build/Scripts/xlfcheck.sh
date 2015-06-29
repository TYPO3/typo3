#!/bin/bash

#########################
#
# Find all xmlns:t3 namespace definition and
# t3:id numbers in XLF language files
# and check their uniqueness.
#
# Use from within TYPO3 CMS source
#
# @author Markus Klein <klein.t3@reelworx.at>
# @date 2014-11-14
#
##########################

XLF_FILES="xlffiles.txt"
XLF_IDS="xlfids.txt"
XLF_XMLNS="xlfxmlns.txt"

function join { local IFS="$1"; shift; echo "$*"; }

echo 'Collecting XLF files'
find typo3/ -name '*.xlf' > $XLF_FILES
XLFCNT=$(cat xlffiles.txt | wc -l)

# Check for xmlns
echo 'Extracting xmlns:t3 information'
sed -ne 's/.*\(xmlns:t3="http:\/\/typo3.org\/schemas\/xliff"\)/\1/pg' `cat $XLF_FILES` | sort > $XLF_XMLNS
XLFXMLNSCNT=$(cat $XLF_XMLNS | wc -l)
DIFFXMLNS=$(join , $(uniq -d $XLF_XMLNS))
MISSINGXMLNS=$(join , $(grep -L 'xmlns:t3="http://typo3.org/schemas/xliff"' `cat $XLF_FILES`))

# Checks for t3:id
echo 'Extracting t3:id information'
sed -ne 's/.*t3:id="\([0-9]\+\)".*/\1/pg' `cat $XLF_FILES` | sort > $XLF_IDS

echo 'Processing ids'
XLFIDCNT=$(cat $XLF_IDS | wc -l)
UXLFIDCNT=$(uniq $XLF_IDS | wc -l)
DIFFIDS=$(join , $(uniq -d $XLF_IDS))
MISSINGIDS=$(join , $(grep -L 't3:id' `cat $XLF_FILES`))

# Cleanup of temporary files
rm $XLF_IDS $XLF_FILES $XLF_XMLNS

if [ $XLFCNT -ne $XLFXMLNSCNT ];
then
	echo "There is at least one XLF file missing a xmlns for t3 (xmlns:t3)."
	echo "Missing in: $MISSINGXMLNS"
	exit 1
fi

if [ $XLFCNT -ne $XLFIDCNT ];
then
	echo "There is at least one XLF file missing a unique ID (t3:id)."
	echo "Missing in: $MISSINGIDS"
	exit 1
fi
if [ $XLFIDCNT -ne $UXLFIDCNT ];
then
	echo "There is an XLF id that does not seem to be UNIQUE."
	echo "Search for t3:id $DIFFIDS"
	exit 1
fi

exit 0
