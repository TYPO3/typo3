
.. include:: ../../Includes.txt

=================================================
Breaking: #69930 - Remove option "serverTimeZone"
=================================================

See :issue:`69930`

Description
===========

The option `$TYPO3_CONF_VARS['SYS']['serverTimeZone']` which was introduced when
there was no clean way to fetch the timezone option in the PHP4 environment, has
been removed. It was solved in PHP 5.1.0 which introduced `date_default_timezone_get()`
which is used by the TYPO3 Core by default.


Impact
======

Accessing the option will result in a PHP notice, as it has been removed in TYPO3 CMS 7.
Extensions making use of this option will result in an unexpected behaviour as
possible calculations are wrong.


Affected Installations
======================

Any TYPO3 installation which uses a third-party extensions that uses this option.


Migration
=========

Use native timezone support by PHP directly. See `date_default_timezone_get()`
for more information.


.. index:: PHP-API, LocalConfiguration
