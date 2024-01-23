.. include:: /Includes.rst.txt

.. _breaking-102151-1697111006:

================================================================
Breaking: #102151 - XML prologue always added in flexArray2Xml()
================================================================

See :issue:`102151`

Description
===========

The second argument :php:`$addPrologue = false` on
:php:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools->flexArray2Xml()`
has been dropped: When imploding a FlexForm array to an XML string using
this method, the "XML prologue" is always added.


Impact
======

This should have no impact for consumers of this method. The counterpart method
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::xml2array()` happily deals with this.


Affected installations
======================

Instances with extensions using :php:`FlexFormTools->flexArray2Xml()` can drop
the second argument. The extension scanner will find usages with a weak match.

Since this is a detail method of the TYPO3 Core FlexForm handling, not often
handled by extensions themselves, few instances will be affected in the first place.


Migration
=========

No data migration needed, PHP consumers should drop the second argument
when calling the method.

.. index:: FlexForm, PHP-API, FullyScanned, ext:core
