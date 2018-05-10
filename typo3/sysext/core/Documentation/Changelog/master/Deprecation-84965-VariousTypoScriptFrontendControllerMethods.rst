.. include:: ../../Includes.txt

==================================================================
Deprecation: #84965 - Various TypoScriptFrontendController methods
==================================================================

See :issue:`84965`

Description
===========

A lot of functionality from TypoScriptFrontendController (a.k.a. `TSFE`) has been migrated
into new PSR-15 middlewares, which are flexible modules to modify a HTTP request workflow.

Most of the functionality which is now in a PSR-15-based middleware is related to setting up various
permission and GET/POST variable resolving.

The following methods within :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` have been marked
as deprecated:

* connectToDB()
* checkAlternativeIdMethods()
* initializeBackendUser()
* handleDataSubmission()
* setCSS()
* convPOSTCharset()

All hooks previously located within these methods still work as expected, as they are now called within
a PSR-15 middleware.

Additionally, there are some methods within TSFE which have been marked as "internal" for a long time,
but had the PHP visibility "public" from a legacy code base. These methods, which are internal for TYPO3 Core
purposes, now have the visibility "protected".

- getPageAndRootline()
- checkRootlineForIncludeSection()
- setSysPageWhereClause()
- checkAndSetAlias()
- getHash()
- getLockHash()
- setUrlIdToken()


Impact
======

Calling any of the deprecated methods above will trigger a PHP deprecation message.


Affected Installations
======================

Any TYPO3 installation with a custom extension setting up or mimicking a custom frontend request by
calling TypoScriptFrontendController methods directly.


Migration
=========

Extensions that bootstrap their own frontend should ensure that the respective Middlewares are run,
e.g. via custom stacks or just by setting up the "frontend" middleware stack.

Additionally, extensions can create custom middlewares to modify a HTTP request or response as well.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend