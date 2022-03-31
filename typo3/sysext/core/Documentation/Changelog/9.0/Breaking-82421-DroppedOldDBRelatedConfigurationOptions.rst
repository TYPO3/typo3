.. include:: /Includes.rst.txt

===============================================================
Breaking: #82421 - Dropped old DB related configuration options
===============================================================

See :issue:`82421`

Description
===========

Some configuration options related to pre-doctrine era have been removed
from :php:`$GLOBALS['TYPO3_CONF_VARS']`:

* `SYS/sqlDebug` - Obsolete since core version 8, no substitution
* `SYS/setDBinit` - Obsolete since core version 8 and migrated automatically
* `SYS/no_pconnect` - Obsolete since core version 8 and migrated automatically
* `SYS/dbClientCompress` - Obsolete since core version 8 and migrated automatically


Impact
======

Extension code usually shouldn't rely on these settings.


Affected Installations
======================

Instances with extension code using these array entries in :php:`$GLOBALS['TYPO3_CONF_VARS']`
are found by the install tool extension scanner.


Migration
=========

Extension code should not rely on these core framework internal settings.

.. index:: Database, LocalConfiguration, PHP-API, FullyScanned
