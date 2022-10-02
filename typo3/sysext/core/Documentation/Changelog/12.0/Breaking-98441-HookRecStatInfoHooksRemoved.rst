.. include:: /Includes.rst.txt

.. _breaking-98441-1664267734:

==================================================
Breaking: #98441 - Hook "recStatInfoHooks" removed
==================================================

See :issue:`98441`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks']`
has been removed from TYPO3 Core.

The hook was used to modify the list of icons in the Page Module and the List module.

More modern solutions have been in place in previous TYPO3 versions already.

Impact
======

Hook implementations in third-party extensions will be ignored.

Affected installations
======================

TYPO3 installations with custom extensions using this hook. Affected extensions
can be detected in the Extension Scanner of the Install Tool.

Migration
=========

For the page module, the new Fluid-based page module (available since TYPO3 v10), allows
to modify the icon list directly in the template.

For list module implementations, the PSR-14 event :php:`ModifyRecordListRecordActionsEvent`
can be used instead since TYPO3 v11.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
