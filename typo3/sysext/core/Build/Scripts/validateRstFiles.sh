#!/bin/bash

#########################
#
# Check all rst snippets
# in typo3/sysext/core/Documentation/Changelog
#
# It expects to be run from the core root.
#
##########################

echo 'Searching for rst snippets'
EXT=rst
COUNT=0
for i in `find typo3/sysext/core/Documentation/Changelog -name "*.rst" -type f`; do
    if [[ "${i}" != "${i%.${EXT}}" && ! $i =~ 'Index.rst' &&  ! $i =~ 'Howto.rst' ]];then


        fileContent=$(cat $i);
        outputFileToStream=0;

# This regex needs to allow whitespace and/or newlines before the .. include:: ../../Includes.txt
# The regex itself is correct, bash doesn't like it
        if ! [[ "$fileContent" =~ ^[[:space:]]*'.. include:: ../../Includes.txt' ]]; then
            INCLUDE="no include"
            include_message="insert '.. include:: ../../Includes.txt' in first line of the file"
            outputFileToStream=1;
        else
            INCLUDE=""
        fi

# This regex seems to have problems with the backtick characters.
# Maybe this is because it somehow interprets them from the variable $fileContent
        if ! [[ "$fileContent" =~ 'See :issue:'\`([0-9]{4,6})\` ]]; then
            REFERENCE="no reference"
            reference_message="insert 'See :issue:\`<issuenumber>\`' after headline"
            outputFileToStream=1;
        else
            REFERENCE=""
        fi

# This regex needs to check that the ..index:: line a) holds valid content and b) is
# the last line in the checked file
        if ! [[ "$fileContent" =~ '.. index:: '((TypoScript|TSConfig|TCA|FlexForm|LocalConfiguration|Fluid|FAL|Database|JavaScript|PHP-API|Frontend|Backend|CLI|RTE|ext:([a-z|A-Z|_|0-9]*))([,|[:space:]]{2})?)+$ ]]; then
            INDEX="no or wrong index"
            index_message="insert '.. index:: <at least one keyword>' at last line of the file"
            outputFileToStream=1;
        else
            INDEX=""
        fi
        # Output filename in case any error was found
        if [ $outputFileToStream == 1 ] ; then
            FILE=${i/#typo3\/sysext\/core\/Documentation\/Changelog\//}
            let COUNT++
            printf "%-10s | %-12s | %-17s | %s \n" "$INCLUDE" "$REFERENCE" "$INDEX" "$FILE";
            if ! [[ -z ${include_message} ]] ; then
            echo ${include_message};
            fi
            if ! [[ -z ${reference_message} ]] ; then
            echo ${reference_message};
            fi
            if ! [[ -z ${index_message} ]] ; then
            echo ${index_message};
            fi
        fi
    fi

done
if [[ $COUNT > 0 ]]; then
     >&2 echo "Found $COUNT rst files with errors, check full log for details.";
     exit 1;
   else
     exit 0;
fi
