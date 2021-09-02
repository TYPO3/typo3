.. include:: ../../Includes.txt

===================================================
Deprecation: #95077 - FileDump CheckFileAccess hook
===================================================

See :issue:`95077`

Description
===========

The TYPO3 Hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['FileDumpEID.php']['checkFileAccess']`
which is executed in the :php:`FileDumpConroller` class, enabiling third-party
code to perfom additional access / security checks before dumping the requested
file, has been deprecated.

The accompanied PHP Interface for the hook
:php:`TYPO3\CMS\Core\Resource\Hook\FileDumpEIDHookInterface` has been marked
as deprecated as well.

Impact
======

If a hook is registered in a TYPO3 installation, a PHP deprecation
message is triggered. The extension scanner also detects any usage
of the deprecated interface as strong, and the definition of the
hook as weak match.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook.

Migration
=========

Migrate to the newly introduced `ModifyFileDumpEvent` PSR-14 event.

.. index:: PHP-API, FullyScanned, ext:core
