.. include:: /Includes.rst.txt

.. _deprecation-97201:

==========================================================================
Deprecation: #97201 - Unused Interface for new content element wizard hook
==========================================================================

See :issue:`97201`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook']`
required hook implementations to implement :php:`\TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface`.

Since the mentioned hook has been :doc:`removed <../12.0/Breaking-97201-RemovedHookForNewContentElementWizard>`,
the interface is not in use anymore and has been marked as deprecated.

Impact
======

Using the interface has no effect anymore and the extension scanner will
report any usage.

Affected Installations
======================

TYPO3 installations using the PHP interface in custom extension code.

Migration
=========

The PHP interface is still available for TYPO3 v12.x, so extensions can
provide a version which is compatible with TYPO3 v11 (using the hook)
and TYPO3 v12.x (using the new :doc:`PSR-14 event <../12.0/Feature-97201-PSR-14EventForModifyingNewContentElementWizardItems>`),
at the same time.

Remove any usage of the PHP interface and use the new PSR-14
event to avoid any further problems in TYPO3 v13+.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
