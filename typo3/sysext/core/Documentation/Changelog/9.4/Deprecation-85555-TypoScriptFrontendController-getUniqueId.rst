.. include:: /Includes.rst.txt

===============================================================
Deprecation: #85555 - TypoScriptFrontendController->getUniqueId
===============================================================

See :issue:`85555`

Description
===========

The unused method :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getUniqueId()` has been marked as
deprecated.


Impact
======

Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions calling this public method directly.


Migration
=========

It is recommended to build a fully unique string functionality in a separate PHP class, if needed, decorated
with a proper singleton pattern, or a runtime cache.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
