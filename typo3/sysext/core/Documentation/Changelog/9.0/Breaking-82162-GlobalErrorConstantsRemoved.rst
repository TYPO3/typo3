.. include:: ../../Includes.txt

=================================================
Breaking: #82162 - Global error constants removed
=================================================

See :issue:`82162`

Description
===========

Three error and logging related constants are no longer defined during TYPO3 core bootstrap:

* :php:`TYPO3_DLOG`
* :php:`TYPO3_ERROR_DLOG`
* :php:`TYPO3_EXCEPTION_DLOG`

Two error and logging related keys have been removed from :php:`TYPO3_CONF_VARS`:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_errorDLOG']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_exceptionDLOG']`


Impact
======

Accessing one of the constants in PHP will return the constants name as value
which is different to the former value and most likely breaks code depending on it.


Affected Installations
======================

The extension scanner of the install tool finds extensions affected by this change.
The install tool will automatically remove the :php:`LocalConfiguration.php` settings
:php:`TYPO3_CONF_VARS` if used.


Migration
=========

Refactor code to not depend on these constants and :php:`TYPO3_CONF_VARS` any longer,
there shouldn't be many use cases where extensions used these.

.. index:: LocalConfiguration, PHP-API, FullyScanned