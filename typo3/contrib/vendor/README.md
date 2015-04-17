## About this folder

This is a completely composer managed folder and should only be updated by performing the following steps:

* Change the `vendor-dir` in the `composer.json` to 'typo3/contrib/vendor'
* Remove a possibly present composer.lock file
* Run `composer update --no-dev -o --prefer-dist`
* Add all changes to the repository
* Make sure this file (which is the only file not managed by composer) stays in this directory and is not removed in the commit

