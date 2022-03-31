.. include:: /Includes.rst.txt

==========================================================
Deprecation: #95089 - ExtendedFileUtility ProcessData hook
==========================================================

See :issue:`95089`

Description
===========

The TYPO3 hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData']`
which can be used to execute additional tasks, after a file operation has
been performed, has been marked as deprecated.

The accompanied PHP interface for the hook
:php:`TYPO3\CMS\Core\Utility\File\ExtendedFileUtilityProcessDataHookInterface`
has been marked as deprecated as well.

Impact
======

If the hook is registered in a TYPO3 installation, a PHP :php:`E_USER_DEPRECATED` error is triggered.
The extension scanner also detects any usage
of the deprecated interface as strong, and the definition of the
hook as weak match.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook.

Migration
=========

Migrate to the newly introduced :php:`AfterFileCommandProcessedEvent` PSR-14 event.

.. index:: PHP-API, FullyScanned, ext:core
