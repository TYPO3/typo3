
.. include:: ../../Includes.txt

================================================================================================
Feature: #68184 - Paths to typo3/cms package and document root can be specified in composer.json
================================================================================================

See :issue:`68184`

Description
===========

With the new composer installer it is possible to specify the path of the document root
and also the path of the typo3/cms package.

It can be specified in the extra section of your composer root package like that:

.. code-block:: javascript

	{
		"repositories": [
			{ "type": "composer", "url": "http://composer.typo3.org/" }
		],
		"name": "typo3/cms-base-distribution",
		"description" : "TYPO3 CMS Base Distribution",
		"license": "GPL-2.0+",
		"config": {
			"vendor-dir": "Packages/Libraries",
			"bin-dir": "bin"
		},
		"require": {
			"typo3/cms": "dev-master"
		},
		"require-dev": {
			"mikey179/vfsStream": "1.3.*@dev",
			"phpunit/phpunit": "~4.4.0",
			"twbs/bootstrap": "3.3.*",
			"fortawesome/font-awesome": "4.2.*"
		},
		"extra": {
			"typo3/cms": {
				"cms-package-dir": "{$vendor-dir}/typo3/cms",
				"web-dir": "web"
			}
		}
	}


Impact
======

When specifying the configuration like mentioned above, the directory structure of a `composer install` will change.
