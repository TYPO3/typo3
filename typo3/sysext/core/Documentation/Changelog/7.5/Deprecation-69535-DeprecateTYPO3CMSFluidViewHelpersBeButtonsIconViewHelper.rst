
.. include:: /Includes.rst.txt

======================================================================================
Deprecation: #69535 - Deprecate \TYPO3\CMS\Fluid\ViewHelpers\Be\Buttons\IconViewHelper
======================================================================================

See :issue:`69535`

Description
===========

`\TYPO3\CMS\Fluid\ViewHelpers\Be\Buttons\IconViewHelper` has been marked as deprecated.


Impact
======

The view helper should not be used any longer and will be removed with TYPO3 CMS 8.


Affected Installations
======================

Extensions which use the view helper.


Migration
=========

Use the core icon viewhelper `\TYPO3\CMS\Core\ViewHelpers\IconViewHelper` instead.

Example: Instead of `<f:be.buttons.icon icon="apps-pagetree-collapse" />` use `<core:icon identifier="apps-pagetree-collapse" />`


.. index:: PHP-API, Fluid, Backend
