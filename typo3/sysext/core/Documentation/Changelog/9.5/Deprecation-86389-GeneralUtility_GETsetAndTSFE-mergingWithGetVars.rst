.. include:: ../../Includes.txt

==============================================================================
Deprecation: #86389 - GeneralUtility::_GETset() and TSFE->mergingWithGetVars()
==============================================================================

See :issue:`86389`

Description
===========

Two methods related to setting global :php:`$_GET` parameters have been marked as deprecated:

* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::_GETset()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->mergingWithGetVars()`

The two methods are wrappers to set the :php:`$_GET` properties, however, this concept has been superseded
by using the PSR-7 request object within PSR-15 middlewares to replace the variables.


Impact
======

Calling any of the two methods within PHP will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any installation making use of these methods within a TYPO3 extension, e.g. RealURL.


Migration
=========

Implement a custom PSR-15 middleware to update the PSR-7 request object, and to manually set :php:`$_GET` on top,
as long as TYPO3 still supports :php:`GeneralUtility::_GP()`, although these methods will vanish in the near future.

Relying on the request object, and using PSR-15 middlewares to manipulate request parameters is more future-proof
for extensions and TYPO3 sites.

.. index:: Frontend, PHP-API, FullyScanned
