#!/bin/bash

#########################
#
# This file is typically executed by travis and / or bamboo.
# It expects to be run from the core root.
#
# ./components/testing_framework/core/Build/Scripts/splitFunctionalTests.sh <numberOfConfigs>
#
# The scripts finds all functional tests and creates <numberOfConfigs> number
# of phpunit .xml configuration files where each configuration lists a weighted
# number of single functional tests.
#
# components/testing_framework/core/Build/FunctionalTests-Job-<counter>.xml
#
#########################

numberOfFunctionalTestJobs=${1}
numberOfFunctionalTestJobsMinusOne=$(( numberOfFunctionalTestJobs - 1 ))

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

# A list of all functional test files
find . -name \*Test.php -path \*typo3/sysext/*/Tests/Functional* > buildTemp/testFiles.txt

# File with test files of format "42 ./path/to/file"
while read testFile; do
	numberOfTestsInTestFile=`grep "@test" ${testFile} | wc -l`
	echo "${numberOfTestsInTestFile} ${testFile}" >> buildTemp/testFilesWithNumberOfTestFiles.txt
done < buildTemp/testFiles.txt

# Sort list of files numeric
cat buildTemp/testFilesWithNumberOfTestFiles.txt | sort -n -r > buildTemp/testFilesWeighted.txt

# Config file boilerplate per job
for (( i=0; i<${numberOfFunctionalTestJobs}; i++)); do
	if [ -f components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml ]; then
		rm components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	fi
	echo '<phpunit' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	backupGlobals="true"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	backupStaticAttributes="false"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	bootstrap="FunctionalTestsBootstrap.php"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	colors="true"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	convertErrorsToExceptions="true"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	convertWarningsToExceptions="true"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	forceCoversAnnotation="false"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	processIsolation="true"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	stopOnError="false"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	stopOnFailure="false"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	stopOnIncomplete="false"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	stopOnSkipped="false"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	verbose="false"' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '>' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	<testsuites>' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '		<testsuite name="Core tests">' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
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
	testFileModuleNumberOfJobs=$(( counter % numberOfFunctionalTestJobs ))
	if [[ ${direction} == "descending" ]]; then
		targetJobNumberForFile=$(( numberOfFunctionalTestJobs - testFileModuleNumberOfJobs))
	else
		targetJobNumberForFile=${testFileModuleNumberOfJobs}
	fi
	if [ ${testFileModuleNumberOfJobs} -eq ${numberOfFunctionalTestJobs} ]; then
		if [[ ${direction} == "descending" ]]; then
			direction=ascending
		else
			direction=descending
		fi
	fi

	echo '			<directory>' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${targetJobNumberForFile}.xml
	echo "				../../../../../${testFile}" >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${targetJobNumberForFile}.xml
	echo '			</directory>' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${targetJobNumberForFile}.xml
	(( counter ++ ))
done < buildTemp/testFilesWeighted.txt

# Final part of config file
for (( i=0; i<${numberOfFunctionalTestJobs}; i++)); do
	echo '		</testsuite>' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '	</testsuites>' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
	echo '</phpunit>' >> components/testing_framework/Resources/Core/Build/FunctionalTests-Job-${i}.xml
done

# Clean up
rm buildTemp/testFiles.txt
rm buildTemp/testFilesWeighted.txt
rm buildTemp/testFilesWithNumberOfTestFiles.txt
rmdir buildTemp
