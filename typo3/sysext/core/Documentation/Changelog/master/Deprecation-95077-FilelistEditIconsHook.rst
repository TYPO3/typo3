.. include:: ../../Includes.txt

============================================
Deprecation: #95077 - Filelist editIconsHook
============================================

See :issue:`95077`

Description
===========

The TYPO3 Hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook']`
which is executed in the :php:`FileList` class to manipulate the icons, used
for the edit control section in the files and folders listing, has been
deprecated.

The accompanied PHP Interface for such hooks
:php:`TYPO3\CMS\Filelist\FileListEditIconHookInterface` has been marked
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

Migrate to the newly introduced `ProcessFileListActionsEvent` PSR-14 event.

.. index:: PHP-API, FullyScanned, ext:filelist
