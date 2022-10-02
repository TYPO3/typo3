.. include:: /Includes.rst.txt

.. _breaking-97174:

========================================================================
Breaking: #97174 - Removed hook for modifying info module footer content
========================================================================

See :issue:`97174`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/web_info/class.tx_cms_webinfo.php']['drawFooterHook']`
has been removed in favor of a new PSR-14 event :php:`\TYPO3\CMS\Info\Controller\Event\ModifyInfoModuleContentEvent`.

Impact
======

Any hook implementation registered is not executed anymore in
TYPO3 v12.0+. The extension scanner will report possible usages.

Affected Installations
======================

All TYPO3 installations using this hook in custom extension code.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the :doc:`PSR-14 event <../12.0/Feature-97174-PSR-14EventForModifyingInfoModuleContent>`
as an improved replacement.

.. index:: Backend, PHP-API, FullyScanned, ext:info
