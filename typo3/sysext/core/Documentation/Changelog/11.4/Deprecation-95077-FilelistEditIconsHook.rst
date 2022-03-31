.. include:: /Includes.rst.txt

============================================
Deprecation: #95077 - Filelist editIconsHook
============================================

See :issue:`95077`

Description
===========

The TYPO3 hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook']`
which is executed in the :php:`FileList` class to manipulate the icons, used
for the edit control section in the files and folders listing, has been marked as
deprecated.

The accompanied PHP interface for such hooks
:php:`TYPO3\CMS\Filelist\FileListEditIconHookInterface` has been marked
as deprecated as well.

Impact
======

If a hook is registered in a TYPO3 installation, a PHP :php:`E_USER_DEPRECATED` error is triggered.
The extension scanner also detects any usage
of the deprecated interface as strong, and the definition of the
hook as weak match.


Affected Installations
======================

TYPO3 installations with custom extensions using this hook.

Migration
=========

Migrate to the newly introduced :php:`\TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent` PSR-14 event.

.. index:: PHP-API, FullyScanned, ext:filelist
