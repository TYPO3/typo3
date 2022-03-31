.. include:: /Includes.rst.txt

====================================================
Deprecation: #90856 - Widget AutoComplete ViewHelper
====================================================

See :issue:`90856`

Description
===========

The Fluid ViewHelper :html:`<f:widget.autocomplete>` and the related controller
:php:`TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\AutocompleteController`
have been marked as deprecated and will be removed in TYPO3 v11.

The widget depends on third-party libraries that cannot be
maintained for a full LTS release lifecycle.


Impact
======

Any usage of this ViewHelper or extending one of the following classes will trigger a PHP :php:`E_USER_DEPRECATED` error:

* :php:`TYPO3\CMS\Fluid\ViewHelpers\Widget\AutocompleteViewHelper`
* :php:`TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\AutocompleteController`


Affected Installations
======================

Any TYPO3 installation with custom templates that contain this ViewHelper.


Migration
=========

Remove any usages within the Fluid templates. There is no replacement provided by the core.
If you need this widget, you have to provide your own implementation with your
own frontend libraries for the handling.

If you still need it, copy the ViewHelper and Controller into an own extension.


.. index:: Fluid, PHP-API, NotScanned, ext:fluid
