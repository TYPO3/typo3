
.. include:: ../../Includes.txt

============================================
Deprecation: #75621 - GeneralUtility methods
============================================

See :issue:`75621`

Description
===========

The following methods within `GeneralUtility` have been marked as deprecated:

* compat_version()
* convertMicrotime()
* deHSCentities()
* slashJS()
* rawUrlEncodeJS()
* rawUrlEncodeFP()
* lcfirst()
* getMaximumPathLength()

The second parameter of :php:`GeneralUtility::wrapJS()` has been removed.


Impact
======

Calling any of the methods above will trigger a deprecation log entry.

Calling :php:`GeneralUtility::wrapJS()` with the second parameter will trigger a PHP notice message.


Affected Installations
======================

Any installation with a third-party extension calling one of the methods in its PHP code.


Migration
=========

For the following methods, use the native PHP methods and constants directly that are used within these methods:

* compat_version()
* convertMicrotime()
* deHSCentities()
* slashJS()
* rawUrlEncodeJS()
* rawUrlEncodeFP()
* lcfirst()
* getMaximumPathLength()

.. index:: PHP-API
