.. include:: /Includes.rst.txt

===============================================================
Breaking: #92990 - AbstractUserAuthentication->svConfig removed
===============================================================

See :issue:`92990`

Description
===========

The public property :php:`svConfig` of the PHP class :php:`AbstractUserAuthentication` is removed.

It served as a short-hand for :php:`$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']`, which was common in TYPO3 v4 days, but is
useless nowadays. This property is removed in favor of a local
variable allowing for further refactoring of the Authentication
process in the future.


Impact
======

Accessing or setting the property has no effect anymore,
and will trigger a PHP warning.


Affected Installations
======================

TYPO3 installations with custom extensions with PHP code accessing the property related to authentication, which is highly unlikely.


Migration
=========

Manipulate the global array :php:`$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']` directly instead,
preferably in :file:`AdditionalConfiguration.php` or in an extensions :file:`ext_localconf.php` file.

.. index:: PHP-API, FullyScanned, ext:core
