.. include:: /Includes.rst.txt

===========================================
Deprecation: #86411 - TSFE->makeCacheHash()
===========================================

See :issue:`86411`

Description
===========

The method :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->makeCacheHash()`
which acts for validating the `&cHash` GET parameter against other given GET parameters
has been marked as deprecated, as this functionality has been moved into a PSR-15 middleware.


Impact
======

Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with extensions calling the PHP method directly.


Migration
=========

Ensure to use the PSR-15 middleware stack with the PageArgumentValidator in use to verify a
given cHash signature against given query parameters.

.. index:: Frontend, FullyScanned
