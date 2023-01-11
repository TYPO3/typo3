.. include:: /Includes.rst.txt

.. _deprecation-99558-1673887807:

===========================================================
Deprecation: #99558 - Deprecate PageRepository->getExtURL()
===========================================================

See :issue:`99558`

Description
===========

The method :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->getExtURL()`
has been marked as deprecated and should not be used any longer.


Impact
======

Calling the method triggers a deprecation level log message since
TYPO3 v12 and will stop working in v13.


Affected installations
======================

:php:`PageRepository->getExtURL()` is a detail method and relatively unlikely
to be used by extensions. The extension scanner will find affected code places.


Migration
=========

The method has been discontinued and there is no direct migration.

If needed, the most simple solution is to copy the method to an extensions
code base and maintain it within the extension.

.. index:: Backend, PHP-API, FullyScanned, ext:core
