.. include:: ../../Includes.txt

=========================================
Deprecation: #82438 - Deprecation methods
=========================================

See :issue:`82438`

Description
===========

The deprecation log related methods from GeneralUtility along with a
related configuration option have been deprecated:

* :php:`GeneralUtility::logDeprecatedFunction()`
* :php:`GeneralUtility::deprecationLog()`
* :php:`GeneralUtility::getDeprecationLogFileName()`
* :php:`GeneralUtility::logDeprecatedViewHelperAttribute()`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog']` has no effect anymore

Deprecations now use PHP method :php:`trigger_error('a message', E_USER_DEPRECATED)` and run
through the logging and exception stack of the TYPO3 core.  In development context deprecations
are turned into exceptions by default and ignored in production context.


Impact
======

The file :file:`typo3conf/deprecation_xy.log` is no longer filled by the core. However, if an
extension still uses methods like :php:`GeneralUtility::logDeprecatedFunction()` it is
still filled with these messages, and throws an additional PHP E_USER_DEPRECATED message.


Affected Installations
======================

Installations with extensions that use one of the above methods.


Migration
=========

Extension authors should switch to :php:`trigger_error('A useful message', E_USER_DEPRECATED);`

.. index:: PHP-API, FullyScanned