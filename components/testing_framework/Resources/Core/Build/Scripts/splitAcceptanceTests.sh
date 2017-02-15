#!/bin/bash

#########################
#
# This file is typically executed by bamboo, but could be
# used locally, too.
#
# It expects to be run from the core root.
#
# ./components/testing_framework/core/Build/Scripts/splitAcceptanceTests.sh <numberOfConfigs>
#
# The script finds all acceptance tests and creates <numberOfConfigs> number
# of codeception group files, each containing a sub set of Cest files to execute.
#
# components/testing_framework/Resources/Core/Build/Configuration/Acceptance/AcceptanceTests-Job-<counter>
#
# Those sub-groups can then be executed with a command like (example for files in group 2 here)
# ./bin/codecept run Acceptance -d -g AcceptanceTests-Job-2 -c components/testing_framework/Resources/Core/Build/AcceptanceTests.yml
#
#########################

numberOfAcceptanceTestJobs=${1}

# Have a dir for temp files and clean up possibly existing stuff
if [ ! -d buildTemp ]; then
	mkdir buildTemp || exit 1
fi
if [ -f buildTemp/testFiles.txt ]; then
	rm buildTemp/testFiles.txt
fi
if [ -f buildTemp/testFilesWithNumberOfTestFiles.txt ]; then
	rm buildTemp/testFilesWithNumberOfTestFiles.txt
fi
if [ -f buildTemp/testFilesWeighted.txt ]; then
	rm buildTemp/testFilesWeighted.txt
fi

# A list of all acceptance test files
find . -name \*Cest.php -path \*typo3/sysext/*/Tests/Acceptance* > buildTemp/testFiles.txt

# File with test files of format "42 ./path/to/file"
while read testFile; do
	numberOfTestsInTestFile=`grep "public function [^\_].*" ${testFile} | wc -l`
	echo "${numberOfTestsInTestFile} ${testFile}" >> buildTemp/testFilesWithNumberOfTestFiles.txt
done < buildTemp/testFiles.txt

# Sort list of files numeric
cat buildTemp/testFilesWithNumberOfTestFiles.txt | sort -n -r > buildTemp/testFilesWeighted.txt

groupFilePath="components/testing_framework/Resources/Core/Build/Configuration/Acceptance"
# Config file boilerplate per job
for (( i=1; i<=${numberOfAcceptanceTestJobs}; i++)); do
	if [ -f ${groupFilePath}/AcceptanceTests-Job-${i} ]; then
		rm ${groupFilePath}/AcceptanceTests-Job-${i}
	fi
	touch ${groupFilePath}/AcceptanceTests-Job-${i}
done

counter=0
direction=ascending
while read testFileWeighted; do
	# test file only, without leading ./
	testFile=`echo ${testFileWeighted} | cut -f2 -d" " | cut -f2-40 -d"/"`

	# Goal: with 3 jobs, have:
	# file #0 to job #0 (asc)
	# file #1 to job #1 (asc)
	# file #2 to job #2 (asc)
	# file #3 to job #2 (desc)
	# file #4 to job #1 (desc)
	# file #5 to job #0 (desc)
	# file #6 to job #0 (asc)
	# ...
	testFileModuleNumberOfJobs=$(( counter % numberOfAcceptanceTestJobs ))
	if [[ ${direction} == "descending" ]]; then
		targetJobNumberForFile=$(( numberOfAcceptanceTestJobs - testFileModuleNumberOfJobs))
	else
		targetJobNumberForFile=${testFileModuleNumberOfJobs}
	fi
	if [ ${testFileModuleNumberOfJobs} -eq ${numberOfAcceptanceTestJobs} ]; then
		if [[ ${direction} == "descending" ]]; then
			direction=ascending
		else
			direction=descending
		fi
	fi
	echo "../../../../../${testFile}" >> ${groupFilePath}/AcceptanceTests-Job-$(( targetJobNumberForFile + 1 ))
	(( counter ++ ))
done < buildTemp/testFilesWeighted.txt

# Clean up
rm buildTemp/testFiles.txt
rm buildTemp/testFilesWeighted.txt
rm buildTemp/testFilesWithNumberOfTestFiles.txt
rmdir buildTemp
