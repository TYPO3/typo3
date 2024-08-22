.. include:: /Includes.rst.txt

.. _deprecation-104764-1724851918:

=====================================================================
Deprecation: #104764 - Fluid TemplatePaths->fillDefaultsByPackageName
=====================================================================

See :issue:`104764`

Description
===========

Method :php:`TYPO3\CMS\Fluid\View\TemplatePaths->fillDefaultsByPackageName()`
has been marked as deprecated in TYPO3 v13 and will be removed in TYPO3 v14.

Fluid template paths should be set directly using the methods
:php:`setTemplateRootPaths()`, :php:`setLayoutRootPaths()` and
:php:`setPartialRootPaths()`, or - even better - be handled by
:php:`ViewFactoryInterface`, which comes as new feature in TYPO3 v13.

See :ref:`feature-104773-1724939348` for more details of the generic
view interface.


Impact
======

Calling :php:`fillDefaultsByPackageName()` triggers a deprecation level
log level entry in TYPO3 v13 and will be removed in TYPO3 v14.


Affected installations
======================

The method is relatively rarely used by extensions directly, a usage in
extbase :php:`ActionController` has been refactored away. The extension
scanner will find candidates.

Note class :php:`TemplatePaths` is marked `@internal` and should not be
used by extensions at all.

Migration
=========

Use :php:`TYPO3\CMS\Core\View\ViewFactoryInterface` to create views with
proper template paths instead. The TYPO3 core extensions come with plenty
of examples on how to do this.

.. index:: PHP-API, FullyScanned, ext:fluid
