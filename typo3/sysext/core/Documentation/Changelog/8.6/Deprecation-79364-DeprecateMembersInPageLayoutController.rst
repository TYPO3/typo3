.. include:: ../../Includes.txt

===============================================================
Deprecation: #79364 - Deprecate members in PageLayoutController
===============================================================

See :issue:`79364`

Description
===========

Deprecate the members :php:`\TYPO3\CMS\Backend\Controller\PageLayoutController::edit_record`
and :php:`\TYPO3\CMS\Backend\Controller\PageLayoutController::new_unique_uid`.


Impact
======

Installation of EXT:compatibility7 is required to continue using this members until they are removed in TYPO3 v9.


Affected Installations
======================

Any installation using the mentioned members :php:`\TYPO3\CMS\Backend\Controller\PageLayoutController::edit_record`
and :php:`\TYPO3\CMS\Backend\Controller\PageLayoutController::new_unique_uid`.

.. index:: PHP-API, Backend