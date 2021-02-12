#!/bin/bash

#########################
#
# Find duplicate exception timestamps and list them.
# Additionally find exceptions that have no exception code.
# Optionally write the list of found exception codes to the standard output stream in JSON format.
#
# It expects to be run from the core root.
#
##########################

# --------------------------
# --- default parameters ---
# --------------------------
print=0
scanPath="typo3/"

ignoreFiles=()
# auto generated file, shouldn't be checked
ignoreFiles+="sysext/core/Tests/Acceptance/Support/_generated/BackendTesterActions.php"
ignoreFiles+="sysext/core/Tests/Acceptance/Support/_generated/InstallTesterActions.php"
# an exception in here throws a code from a previous exception/error
ignoreFiles+="sysext/extbase/Classes/Core/Bootstrap.php"
ignoreFiles+="sysext/form/Classes/Mvc/Property/Exception/TypeConverterException.php"
ignoreFiles+="sysext/core/Classes/Database/Driver/PDOStatement.php"
ignoreFiles+="sysext/core/Classes/Database/Driver/PDOConnection.php"
ignoreFiles+="sysext/frontend/Classes/Typolink/PageLinkBuilder.php"

# both ActionController and AbstractController throw the same exceptions
# until AbstractController is removed
ignoreFiles+="sysext/extbase/Classes/Mvc/Controller/AbstractController.php"

# ------------------------
# --- print usage info ---
# ------------------------
usage()
{
    echo "Usage: $0 [options]                                           "
    echo " "
    echo "No arguments/default: Check exception numbers for duplicates. "
    echo " "
    echo "Options:                                                      "
    echo " -p                                                           "
    echo "      Specifies whether the list of exceptions found should   "
    echo "      be output as JSON in the standard output stream.        "
    echo " "
    echo " -h                                                           "
    echo "      Show this help.                                         "
    echo " "
    exit 0
}

# -----------------------
# --- parsing of args ---
# -----------------------
OPTIND=1

while getopts "hp" opt;do
    case "$opt" in
    h)
        usage
        ;;
    p)
        print=1
        ;;
    *)
        exit 1
        ;;
    esac
done

shift $((OPTIND-1))

# ------------------------------------------------
# --- print list of found exceptions to stdout ---
# ------------------------------------------------
print_exceptions() {
    IFS=$'\n' sorted=($(sort -u <<<"${exceptionCodes[*]}")); unset IFS

    local numExceptions=${#sorted[@]}

    printf "{\n"
    printf "    \"exceptions\": {\n"
    if [ ${numExceptions} -gt 0 ]; then
      for (( i=0; i<${numExceptions}-1; i++ ));
      do
        printf "        \"%s\":\"%s\",\n" "${sorted[$i]}" "${sorted[$i]}"
      done
      printf "        \"%s\":\"%s\"\n" "${sorted[${numExceptions}-1]}" "${sorted[${numExceptions}-1]}"
    fi
    printf "    },\n"
    printf "    \"total\":%s\n" "${numExceptions}"
    printf "}\n"
}

# -------------------------------------------------------------------------------
# --- check PHP files recursively for missing and duplicate exception numbers ---
# -------------------------------------------------------------------------------
scan_exceptions() {
    local foundNewFile=0
    local oldFilename=""
    local firstLineOfMatch=""
    local foundExceptionInFile=1
    local exceptionCodes=()

    # grep
    # '-r' recursive
    # '--include '*.php'' in all .php files
    # '-Pzoab' pcre regex, -zo remove all linebreaks for multiline match, treat all files as text, output position "filename:position: match", binary position
    #
    # (?:(?!Exception\()[\w\\])*  negative lookahead. capture all alphanum and \ until we reach "Exception("
    # eat "Exception("
    # (?:(?!\);).|[\r\n])*\);[\r\n]+   negative lookahead again, eat everything including a \n until we reach the first ");", then line breaks

    cd "$scanPath" || exit 1

    grep \
        -r \
        --include '*.php' \
        -Pzoab \
        'new (?:(?!Exception\()[\w\\])*Exception\((?:(?!\);).|[\r\n])*\);[\r\n]+' \
        | \
    {
        while read line;
        do
            possibleFilename=`echo ${line} | cut -d':' -f1`
            if [[ ${possibleFilename} =~ .php$ ]]; then
                # the matched line consists of a file name match, we're dealing with a new match here.
                foundNewFile=1
                oldFilename=${currentFilename}
                currentFilename=${possibleFilename}
            else
                foundNewFile=0
            fi

            # skip file if in blacklist
            if [[ {$ignoreFiles[@]} =~ ${currentFilename} ]]; then
                continue
            fi

            # check for match in previous file name
            if [[ ${foundNewFile} -eq 1 ]] && [[ ${foundExceptionInFile} -eq 0 ]]; then
                if [ "$print" -ne "1" ]; then
                    # checking exception codes: exit
                    # listing exception codes: ignore
                    echo "File: $oldFilename"
                    echo "The created exception contains no 10 digit exception code as second argument, in or below this line:"
                    echo "$firstLineOfMatch"
                    exit 1
                fi
            fi

            # reset found flag if we're handling new file
            if [[ ${foundNewFile} -eq 1 ]]; then
                foundExceptionInFile=0
                firstLineOfMatch=${line}
            fi

            # see if the line consists of an exception code
            if [[ "$line" =~ .*([0-9]{10}).* ]]; then
                foundExceptionInFile=1
                exceptionCode=${BASH_REMATCH[1]}
                # check if that code was registered already
                if [[ " ${exceptionCodes[@]} " =~ " ${exceptionCode} " ]]; then
                    if [ "$print" -ne "1" ]; then
                        # checking exception codes: exit
                        # listing exception codes: ignore
                        echo "Duplicate exception code ${exceptionCode} in file:"
                        echo ${currentFilename}
                        exit 1
                    fi
                fi
                exceptionCodes+=(${exceptionCode})
            fi
        done || exit 1

        if [ "$print" -eq "1" ]; then
            print_exceptions
        fi

        exit 0
    }

    exitCode=$?

    cd - > /dev/null

    exit $exitCode
}

scan_exceptions
