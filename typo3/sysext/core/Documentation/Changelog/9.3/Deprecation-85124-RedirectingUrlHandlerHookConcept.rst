.. include:: ../../Includes.txt

=========================================================
Deprecation: #85124 - Redirecting urlHandler Hook Concept
=========================================================

See :issue:`85124`

Description
===========

The URL handler concept introduced in TYPO3 v7 to allow pages to do redirects has been deprecated in favor
of using PSR-7 / PSR-15 middlewares.

The Redirect URL handlers were used for e.g. jumpURLs, pages that should redirect to a external URL
or special handlings registered via the :php:`\TYPO3\CMS\Frontend\Http\UrlHandlerInterface`.

All functionality and methods have been marked as deprecated and will be removed in TYPO3 v10.0.


Impact
======

Calling :php:`$TSFE->initializeRedirectUrlHandlers()` and :php:`$TSFE->redirectToExternalUrl()` will
trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 Installations with extensions registering a urlHandler via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlHandlers']`.


Migration
=========

Check the extension scanner if the site is affected and migrate to a PSR-15 middleware.

.. index:: Frontend, PHP-API, FullyScanned
