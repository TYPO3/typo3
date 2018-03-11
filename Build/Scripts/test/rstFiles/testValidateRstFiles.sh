#!/bin/bash

###########################
#
# Test validateRstFiles.php
#
###########################

# --------------
# automatic vars
# --------------
savedir=$(pwd)
scriptdir=$(dirname $0)
cd $scriptdir
scriptdir=$(pwd)
progname=$(basename $0)


ls data | while read testdir;do
   echo "testing $testdir"
   ../../validateRstFiles.php -d data/$testdir >/dev/null 2>/dev/null
   if [ $? -ne 1 ];then
      echo " "
      echo "$progname: ERROR: test on data/$testdir should return exit code 1"
   fi
done

echo " "
echo "$progname: all tests ok"

cd $savedir