.. include:: /Includes.rst.txt

.. _deprecation-97312:

===================================================
Deprecation: #97312 - Deprecate CSH-related methods
===================================================

See :issue:`97312`

Description
===========

In order to be less breaking for extension authors, classes related to Context
Sensitive Help (CSH) have been marked as deprecated:

* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\HelpButton`

In order to be less breaking for extension authors, methods related to Context
Sensitive Help (CSH) have been marked as deprecated:

* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::cshItem()`
* :php:`TYPO3\CMS\Backend\Template\Components\ButtonBar::makeHelpButton()`
* :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr()`

Also, the following Fluid view helpers are marked as deprecated:

* `f:be.buttons.csh` (:php:`TYPO3\CMS\Fluid\ViewHelpers\Be\Buttons\CshViewHelper`)
* `f:be.labels.csh` (:php:`TYPO3\CMS\Fluid\ViewHelpers\Be\Labels\CshViewHelper`)

Impact
======

Using any of the deprecated classes and methods will trigger a PHP :php:`E_USER_DEPRECATED` error,
with an exception of :php:`ExtensionManagementUtility::addLLrefForTCAdescr()`
for being a low-level method. The extension scanner will report any usage.

Affected Installations
======================

All extensions using any of the deprecated classes and methods are affected.

Migration
=========

Context Sensitive Help is aimed to get removed in TYPO3 v13, no migration is available.

.. index:: Backend, Fluid, PHP-API, PartiallyScanned, ext:backend
