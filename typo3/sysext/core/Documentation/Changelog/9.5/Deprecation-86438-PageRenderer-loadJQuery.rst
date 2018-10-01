.. include:: ../../Includes.txt

================================================
Deprecation: #86438 - PageRenderer->loadJQuery()
================================================

See :issue:`86438`

Description
===========

The method
:php:`TYPO3\CMS\Core\Page\PageRenderer->loadJQuery()`
and the constants
:php:`TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST` and
:php:`TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_NONE` have been marked as deprecated.


Impact
======

Calling this method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom or third party extensions, which use the method.


Migration
=========

Use a package manager for frontend or custom jQuery files instead.

.. index:: Backend, Frontend, PHP-API, FullyScanned
