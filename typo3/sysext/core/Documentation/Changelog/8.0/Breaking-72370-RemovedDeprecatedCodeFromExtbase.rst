
.. include:: /Includes.rst.txt

=======================================================
Breaking: #72370 - Removed deprecated code from extbase
=======================================================

See :issue:`72370`

Description
===========

Remove deprecated code from extbase

The ChangeLog file has been removed.
`TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject::__wakeup()` has been removed.
`TYPO3\CMS\Extbase\Utility\ExtensionUtility::configureModule()` has been removed.


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use calls to the methods above.


Migration
=========

Use the according method in `\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::configureModule()` instead of the removed one from ext:extbase.
Objects are instantiated differently calling `parent::__wakeup()` is no longer necessary. No migration needed.

.. index:: PHP-API, ext:extbase
