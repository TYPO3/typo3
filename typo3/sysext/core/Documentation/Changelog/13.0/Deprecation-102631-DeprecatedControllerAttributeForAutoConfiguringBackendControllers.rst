.. include:: /Includes.rst.txt

.. _deprecation-102631-1702031387:

===============================================================================================
Deprecation: #102631 - Deprecated Controller attribute for auto configuring backend controllers
===============================================================================================

See :issue:`102631`

Description
===========

In order to unify PHP attribute naming, the former :ref:`introduced <feature-99055-1668096727>`
:php:`\TYPO3\CMS\Backend\Attribute\Controller` attribute has been deprecated
and is replaced by the :ref:`new <feature-102631-1702031335>`
:php:`\TYPO3\CMS\Backend\Attribute\AsController` attribute.

Impact
======

The attribute has changed from :php:`\TYPO3\CMS\Backend\Attribute\Controller`
to :php:`\TYPO3\CMS\Backend\Attribute\AsController` and the old name
has been deprecated.

Affected installations
======================

All installations using the deprecated attribute
:php:`\TYPO3\CMS\Backend\Attribute\Controller`. The extension
scanner will report usages.

Migration
=========

Replace usages with the new attribute
:php:`\TYPO3\CMS\Backend\Attribute\AsController`
in custom extension code.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
