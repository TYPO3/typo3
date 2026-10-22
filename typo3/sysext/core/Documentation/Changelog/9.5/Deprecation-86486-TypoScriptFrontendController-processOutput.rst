.. include:: /Includes.rst.txt

.. _deprecation-86486:

===================================================================
Deprecation: #86486 - TypoScriptFrontendController->processOutput()
===================================================================

See :issue:`86486`

Description
===========

The method :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->processOutput()` has been
marked as deprecated.


Impact
======

Calling this method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with extensions that use the method.


Migration
=========

Use :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->applyHttpHeadersToResponse()` and
:php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->processContentForOutput()` instead, if necessary.

.. index:: Frontend, PHP-API, FullyScanned
