.. include:: /Includes.rst.txt

=======================================================
Feature: #84153 - Introduce a generic Environment class
=======================================================

See :issue:`84153`

Description
===========

A new base API class :php:`TYPO3\CMS\Core\Core\Environment` has been added. This class contains application-wide
information related to paths and PHP internals, which were previously exposed via PHP constants.

This Environment class comes with a new possibility, to have a `config` and `var` folder outside of the document root
(known as `PATH_site`). When the environment variable :php:`TYPO3_PATH_APP` is set, which defines the project root
folder, the new `config` and `var` folders outside of the document root are used for installation-wide configuration and
volatile files.

The following static API methods are exposed within the Environment class:

* `Environment::isCli()` - defines whether TYPO3 runs on a CLI context or HTTP context
* `Environment::getApplicationContext()` - returns the ApplicationContext object that encapsulates `TYPO3_CONTEXT`
* `Environment::isComposerMode()` - defines whether TYPO3 was installed via composer
* `Environment::getProjectPath()` - returns the absolute path to the root-level folder without the trailing slash
* `Environment::getPublicPath()` - returns the absolute path to the publically accessible folder (previously known as PATH_site) without the trailing slash
* `Environment::getVarPath()` - returns the absolute path to the folder where non-public semi-persistent files can be stored. For regular projects, this is known as PATH_site/typo3temp/var
* `Environment::getConfigPath()` - returns the absolute path to the folder where (writeable) configuration is stored. For regular projects, this is known as PATH_site/typo3conf
* `Environment::getCurrentScript()` - the absolute path and filename to the currently executed PHP script
* `Environment::isWindows()` - whether TYPO3 runs on a windows server
* `Environment::isUnix()` - whether TYPO3 runs on a unix server


Impact
======

You should not rely on the PHP constants anymore, but rather use the Environment class to resolve paths:

* :php:`PATH_site`
* :php:`PATH_typo3conf`
* :php:`PATH_site . 'typo3temp/var/'`
* :php:`TYPO3_OS`
* :php:`TYPO3_REQUESTTYPE_CLI`
* :php:`PATH_thisScript`

.. index:: PHP-API
