#!/usr/bin/env bash

#########################
#
# Check all UTF-8 files do not contain BOM.
#
# It expects to be run from the core root.
#
##########################

FILES=`find . -type f \
    ! -path "./bin/*" \
    ! -path "./typo3conf/*" \
    ! -path "./Build/node_modules/*" \
    ! -path "./typo3temp/*" \
    ! -path "./vendor/*" \
    ! -path "./fileadmin/*" \
    ! -path "./.git/*" \
    ! -path "./index.php" \
    ! -path "./.php_cs.cache" \
    ! -path "./typo3/sysext/rte_ckeditor/Resources/Public/JavaScript/Contrib/*" \
    ! -path "./typo3/sysext/rte_ckeditor/Resources/Public/JavaScript/Plugins/*" \
    ! -path "./Build/bamboo/target/*" \
    ! -path "./Build/JavaScript/*" \
    ! -path "./typo3/sysext/*/Documentation-GENERATED-temp/*" \
    -print0 | xargs -0 -n1 -P8 file {} | grep 'UTF-8 Unicode (with BOM)'`

if [ -n "${FILES}" ]; then
    echo "Found UTF-8 files with BOM:";
    echo ${FILES};
    exit 1;
fi

exit 0
