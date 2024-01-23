.. include:: /Includes.rst.txt

.. _breaking-102793-1704798752:

=============================================================
Breaking: #102793 - PageRepository->enableFields hook removed
=============================================================

See :issue:`102793`

Description
===========

One of the common PHP APIs used in TYPO3 Core for fetching records is
:php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository`. The method
:php:`enableFields()` is marked as deprecated, and the according hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns']`
has been removed.


Impact
======

Hook listeners will not be executed anymore.


Affected installations
======================

TYPO3 installations with custom extensions using the mentioned hook.


Migration
=========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns']`
can be replaced by a listener to the newly introduced
:doc:`PSR-14 event <../13.0/Feature-102793-PSR-14EventForModifyingDefaultConstraintsInPageRepository>`.

.. index:: Database, Frontend, PHP-API, NotScanned, ext:core
