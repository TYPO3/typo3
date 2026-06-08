.. include:: /Includes.rst.txt

.. _breaking-110027-1781042985:

==============================================================
Breaking: #110027 - StandardContentPreviewRenderer streamlined
==============================================================

See :issue:`110027`

Description
===========

:php:`\TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer` has been turned
into a stateless, dependency-injected service. It is now declared :php:`final`
and can no longer be subclassed. The lazy `initialize()` workaround that
resolved its dependencies through
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()` on first use has
been removed, and all dependencies are passed through the constructor instead.

Impact
======

Custom preview renderers extending
:php:`\TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer` raise a fatal
error, as the class is now :php:`final`.

Affected installations
======================

Instances with third-party extensions that subclass
:php:`StandardContentPreviewRenderer` or instantiate it manually.

Migration
=========

Instead of subclassing :php:`StandardContentPreviewRenderer`, implement
:php:`\TYPO3\CMS\Backend\Preview\PreviewRendererInterface` directly. The standard
renderer may be composed and its rendering methods delegated to where its output
is desired, as :php:`\TYPO3\CMS\Form\Preview\FormPagePreviewRenderer` demonstrates.

.. index:: Backend, PHP-API, NotScanned, ext:backend
