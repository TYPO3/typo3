.. include:: ../../Includes.txt

============================================================================
Deprecation: #85801 - GeneralUtility::explodeUrl2Array - 2nd method argument
============================================================================

See :issue:`85801`

Description
===========

The second argument in :php:`TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array()` has been marked as deprecated.

Setting this (optional) argument to :php:`true` calls the native PHP function :php:`parse_str()`, which
should be used instead directly.


Impact
======

Calling the method with an explicitly set second argument will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with a custom extension calling the method above with a second method argument.


Migration
=========

If the second argument was set to :php:`true` before, use the native PHP function :php:`parse_str()`, if the
second parameter was set to :php:`false` before, just remove it from the method call.

.. index:: PHP-API, FullyScanned, ext:core
