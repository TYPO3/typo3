.. include:: ../../Includes.txt

==========================================================
Deprecation: #86438 - Deprecate PageRenderer->loadJQuery()
==========================================================

See :issue:`86438`

Description
===========

The method :php:`PageRenderer->loadJQuery()` and the constants :php:`PageRenderer::JQUERY_VERSION_LATEST` and :php:`PageRenderer::JQUERY_NAMESPACE_NONE` have been marked as deprecated.


Impact
======

Calling this method will trigger a PHP deprecation notice.


Affected Installations
======================

TYPO3 installations with custom or thrid party extensions, which use the method.


Migration
=========

Use a package manager for frontend or custom jQuery files instead.

.. index:: Backend, Frontend, PHP-API, FullyScanned
