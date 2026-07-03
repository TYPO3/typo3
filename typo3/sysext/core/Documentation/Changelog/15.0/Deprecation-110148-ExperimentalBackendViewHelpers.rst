.. include:: /Includes.rst.txt

.. _deprecation-110148-1751533200:

=======================================================
Deprecation: #110148 - Experimental backend ViewHelpers
=======================================================

See :issue:`110148`

Description
===========

The following experimental backend-related Fluid ViewHelpers have been marked
as deprecated:

*   :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\PagePathViewHelper` (:html:`<f:be.pagePath>`)
*   :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper`

The :php:`PagePathViewHelper` rendered the current page path as displayed in
TYPO3 backend modules. This information is nowadays part of the module doc
header, which is rendered by :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate`
within the corresponding controller.

The abstract :php:`AbstractBackendViewHelper` provided the helper methods
:php:`getModuleTemplate()` and :php:`getPageRenderer()`. Both are obsolete with
the current :php:`ModuleTemplate` view strategy and dependency injection, and
the class is no longer used as a base class within TYPO3 Core.

Impact
======

Using the :html:`<f:be.pagePath>` ViewHelper in a Fluid template will trigger a
PHP :php:`E_USER_DEPRECATED` error.

Extending :php:`AbstractBackendViewHelper` or calling its methods
:php:`getModuleTemplate()` or :php:`getPageRenderer()` will trigger a PHP
:php:`E_USER_DEPRECATED` error.

Both classes will be removed in TYPO3 v16.0.

Affected installations
======================

All installations using the :html:`<f:be.pagePath>` ViewHelper in backend Fluid
templates, or custom backend ViewHelpers extending
:php:`AbstractBackendViewHelper`.

The extension scanner reports any usage of the affected classes as strong match.

Migration
=========

For the page path, use the doc header provided by
:php:`\TYPO3\CMS\Backend\Template\ModuleTemplate` in your backend controller,
which already displays the current page path.

Custom backend ViewHelpers should extend
:php:`\TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper` directly and
retrieve :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate` or
:php:`\TYPO3\CMS\Core\Page\PageRenderer` via dependency injection instead of the
removed helper methods.

.. index:: Fluid, FullyScanned, ext:fluid
