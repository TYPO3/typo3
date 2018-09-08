.. include:: ../../Includes.txt

==================================================
Deprecation: #86197 - Protected FileListController
==================================================

See :issue:`86197`

Description
===========

The following properties changed their visibility from public to protected and should not be called any longer:

* :php:`TYPO3\CMS\Filelist\Controller\FileListController->MOD_MENU`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->MOD_SETTINGS`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->doc`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->id`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->pointer`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->table`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->imagemode`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->cmd`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->filelist`

The following methods changed their visibility from public to protected and should not be called any longer:

* :php:`TYPO3\CMS\Beuser\Controller\BackendUserController->initializeView()`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->menuConfig()`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->initializeView()`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->initializeIndexAction()`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->indexAction()`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->missingFolderAction()`
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->searchAction()`

Additionally, first constructor argument :php:`$fileListController` of class
:php:`TYPO3\CMS\Filelist\FileList` is now optional, class property :php:`$fileListController`
should not be used any longer in hooks of that class.


Impact
======

Calling one of the above properties or methods from a third party object triggers a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Controllers of the core are usually not called by extensions directly, but only through core routing and
dispatching mechanisms. Extensions are unlikely to be affected by this change.


Migration
=========

No migration possible.

.. index:: Backend, PHP-API, NotScanned, ext:filelist