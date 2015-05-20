#!/bin/bash

#########################
#
# Find all t3:id numbers in XLF language files
# and check their uniqueness.
#
# @author Markus Klein <klein.t3@reelworx.at>
# @date 2014-11-14
#
##########################

function join { local IFS="$1"; shift; echo "$*"; }

find typo3 -name '*.xlf' > xlffiles.txt
sed -ne 's/.*t3:id="\([0-9]\+\)".*/\1/pgM' `cat xlffiles.txt` | sort > xlfids.txt
uniq xlfids.txt > uxlfids.txt
XLFCNT=$(cat xlffiles.txt | wc -l)
XLFIDCNT=$(cat xlfids.txt | wc -l)
UXLFIDCNT=$(cat uxlfids.txt | wc -l)
DIFFIDS=$(join , $(uniq -d xlfids.txt))
MISSINGIDS=$(join , $(grep -LP 't3:id' `cat xlffiles.txt`))
rm xlfids.txt uxlfids.txt xlffiles.txt
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
