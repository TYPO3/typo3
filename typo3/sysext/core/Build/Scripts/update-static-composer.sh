#!/bin/bash

[ -d typo3/contrib/vendor ] || exit 1

rm -rf typo3/contrib/vendor/* composer.lock

sed -i '' 's#Packages/Libraries#typo3/contrib/vendor#g' composer.json

composer remove typo3/cms-composer-installers --update-no-dev
composer update --no-dev
git checkout composer.json
