.. include:: /Includes.rst.txt

=======================================
Deprecation: #85613 - Category Registry
=======================================

See :issue:`85613`

Description
===========

With :issue:`94622` the new TCA type `category` has been introduced
as a replacement for the :php:`\TYPO3\CMS\Core\Category\CategoryRegistry`.
Therefore, the :php:`CategoryRegistry` together with
:php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable()`
method have been marked as deprecated and will be removed in TYPO3 v12.

The main reasons for this replacement are:

* Using a dedicated type is more intuitive and consistent
* No more :file:`TCA/Overrides` are necessary for defining category fields
* The new implementation is state of the art (e.g. direct usage of
  the Doctrine API for automatically adding the database columns)


Impact
======

Defining category fields for tables with
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables']` or
by calling :php:`ExtensionManagementUtility::makeCategorizable()` will
trigger a PHP :php:`E_USER_DEPRECATED` error.

The extension scanner will furthermore detect any call to
:php:`ExtensionManagementUtility::makeCategorizable()` and
:php:`CategoryRegistry` as strong match and any usage of
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables']`
as weak match.


Affected Installations
======================

All installations registering category fields using
:php:`ExtensionManagementUtility::makeCategorizable()` or defining
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables']`.

Furthermore, all installations, which directly access the :php:`CategoryRegistry`.


Migration
=========

Directly define category fields in the corresponding TCA, using the :php:`category`
TCA type. Have a look at the corresponding
:doc:`changelog <../11.4/Feature-94622-NewTCATypeCategory>`, for code
examples.

.. index:: PHP-API, TCA, PartiallyScanned, ext:core
