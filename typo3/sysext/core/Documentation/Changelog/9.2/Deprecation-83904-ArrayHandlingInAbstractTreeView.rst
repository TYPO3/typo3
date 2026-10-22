.. include:: /Includes.rst.txt

.. _deprecation-83904:

========================================================
Deprecation: #83904 - Array handling in AbstractTreeView
========================================================

See :issue:`83904`

Description
===========

Handling arrays instead of database relations in class :php:`TYPO3\CMS\Backend\Tree\View\AbstractTreeView`
has been marked as deprecated.


Impact
======

Calling the following methods will throw deprecation warnings and will be removed with core version 10:

* [scanned] :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->setDataFromArray`
* [scanned] :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->setDataFromTreeArray`

The following class properties should not be used any longer and will be removed with core version 10:

* [not scanned] :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->data`
* [scanned] :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->dataLookup`
* [scanned] :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->subLevelID`


Affected Installations
======================

This feature was rarely used, it is pretty unlikely an instance is affected by a consuming extension.
The extension scanner will report most use cases.


Migration
=========

No migration available.

.. index:: Backend, PHP-API, PartiallyScanned
