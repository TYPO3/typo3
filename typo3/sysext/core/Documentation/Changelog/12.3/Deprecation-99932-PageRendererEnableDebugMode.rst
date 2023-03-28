.. include:: /Includes.rst.txt

.. _deprecation-99932-1676186779:

================================================================
Deprecation: #99932 - PageRenderer::removeLineBreaksFromTemplate
================================================================

See :issue:`99932`

Description
===========

The following method has been marked as deprecated and will be removed
in TYPO3 v13:

*   :php:`\TYPO3\CMS\Core\Page\PageRenderer::enableDebugMode()`

The method acts as as shortcut to quickly disable some functions in the backend
context to ease output inspection. However, the properties set by the
method are ignored in the backend context anyway, the method is obsolete.

Impact
======

Using the method will raise a deprecation level log entry and will stop
working in TYPO3 v13.


Affected installations
======================

Instances with extensions that call the method are affected.

The extension scanner reports usages as a weak match.


Migration
=========

All calls to the deprecated messages should be removed from the codebase.

.. index:: Backend, TCA, FullyScanned, ext:core
