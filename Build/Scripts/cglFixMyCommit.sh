#!/usr/bin/env bash

#########################
#
# CGL fix.
#
# It expects to be run from the core root.
#
# To auto-fix single files, use the php-cs-fixer command directly
# substitute $FILE with a filename
#
##########################

# --------------------------
# --- default parameters ---
# --------------------------
# check files in last commit
filestype=commit
# non-dryrun is default
DRYRUN=""
DRYRUN_OPTIONS="--dry-run --diff --diff-format udiff"

# ----------------------
# --- automatic vars ---
# ----------------------
progname=$(basename $0)

# ------------------------
# --- print usage info ---
# ------------------------
usage()
{
    echo "Usage: $0 [options]                                      "
    echo " "
    echo "no arguments/default: fix all php files in last commit   "
    echo " "
    echo "Options:                                                 "
    echo " -f <commit|cache|stdin>                                 "
    echo "      specifies which files to check:                    "
    echo "      - commit (default): all files in latest commit     "
    echo "      - cache : all files in git cache (staging area)    "
    echo "      - stdin : read list of files from stdin            "
    echo " "
    echo " -n                                                      "
    echo "      dryrun only, do not fix anything!                  "
    echo " "
    echo " -h                                                      "
    echo "      help                                               "
    echo " "
    echo "Note: In order to still support command line options of  "
    echo " older versions of this script, you can use the argument "
    echo " dryrun.                                                 "
    echo " "
    echo " THIS IS NOT RECOMMENDED but will still work for now     "
    echo " Usage: $0 [options] [dryrun]                            "
    exit 0
}

# -----------------------
# --- parsing of args ---
# -----------------------
OPTIND=1

while getopts "hnf:" opt;do
    case "$opt" in
    h)
        usage
        ;;
    f)
        filestype=$OPTARG
        echo "$0 files type=$filestype"
        ;;
    n)
        echo "$progname: dryrun mode"
        DRYRUN="$DRYRUN_OPTIONS"
        ;;
    esac
done

shift $((OPTIND-1))

if [ "$1" = "dryrun" ]
then
    echo "$progname: dryrun mode"
    DRYRUN="$DRYRUN_OPTIONS"
fi

# --------------------------------------
# --- check if php executable exists ---
# --------------------------------------
exist_php_executable() {
    which php >/dev/null 2>/dev/null
    if [ $? -ne 0 ];then
        echo "$progname: No php executable found\n"
        exit 1
    fi
}


# ------------------------------
# --- run php without xdebug ---
# ------------------------------
php_no_xdebug ()
{
    temporaryPath="$(mktemp -t php.XXXX).ini"
    php -i | grep "\.ini" | grep -o -e '\(/[A-Za-z0-9._-]\+\)\+\.ini' | grep -v xdebug | xargs awk 'FNR==1{print ""}1' > "${temporaryPath}"
    php -n -c "${temporaryPath}" "$@"
    RETURN=$?
    rm -f "${temporaryPath}"
    exit $RETURN
}

# ------------------------------------
# --- get a list of files to check ---
# ------------------------------------
if [[ $filestype == commit ]];then
    echo "$progname: Searching for php files in latest git commit ..."
    DETECTED_FILES=`git diff-tree --no-commit-id --name-only -r HEAD | grep '.php$' 2>/dev/null`
elif [[ $filestype == cache ]];then
    echo "$progname: Searching for php files in git cache ..."
    DETECTED_FILES=`git diff --cached --name-only | grep '.php$' 2>/dev/null`
elif [[ $filestype == stdin ]];then
    echo "$progname: reading list of php files to check from stdin"
    DETECTED_FILES=$(cat)
else
    echo "$progname: ERROR: unknown filetype, possibly used -f with wrong argument"
    usage
fi
if [ -z "${DETECTED_FILES}" ]
then
    echo "$progname: No PHP files to check, all is well."
    exit 0
fi

# ---------------------------------
# --- run php-cs-fixer on files ---
# ---------------------------------
exist_php_executable
php_no_xdebug ./bin/php-cs-fixer fix \
    -v ${DRYRUN} \
    --path-mode intersection \
    --config=Build/.php_cs \
    `echo ${DETECTED_FILES} | xargs ls -d 2>/dev/null`

exit $?
