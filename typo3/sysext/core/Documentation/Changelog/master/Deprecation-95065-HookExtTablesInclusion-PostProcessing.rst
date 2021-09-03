.. include:: ../../Includes.txt

============================================================
Deprecation: #95065 - Hook extTablesInclusion-PostProcessing
============================================================

See :issue:`95065`

Description
===========

The TYPO3 Hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing']`
which is executed after :php`ext_tables.php` files are included has been marked
as deprecated.

The accompanied PHP Interface for such hooks
:php:`TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface` is
marked as deprecated as well.


Impact
======

If a hook is registered in a TYPO3 installation, a PHP deprecation
message is triggered.


Affected Installations
======================

TYPO3 installations with custom extensions using this hook.


Migration
=========

Migrate to PSR-14 hooks, mainly the newly introduced `BootCompletedEvent` and
the existing `AfterTcaCompilationEvent` depending on the use-case.

.. index:: PHP-API, FullyScanned, ext:core
