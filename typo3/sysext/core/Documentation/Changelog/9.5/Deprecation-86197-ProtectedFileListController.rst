.. include:: ../../Includes.txt

==================================================
Deprecation: #86197 - Protected FileListController
==================================================

See :issue:`86197`

Description
===========

The following properties of class :php:`TYPO3\CMS\Filelist\Controller\FileListController` changed their visibility from public to protected and should not be called any longer:

* :php:`MOD_MENU`
* :php:`MOD_SETTINGS`
* :php:`doc`
* :php:`id`
* :php:`pointer`
* :php:`table`
* :php:`imagemode`
* :php:`cmd`
* :php:`filelist`

The following methods of class :php:`TYPO3\CMS\Filelist\Controller\FileListController`  changed their visibility from public to protected and should not be called any longer:

* :php:`menuConfig()`
* :php:`initializeView()`
* :php:`initializeIndexAction()`
* :php:`indexAction()`
* :php:`missingFolderAction()`
* :php:`searchAction()`

Also, :php:`TYPO3\CMS\Beuser\Controller\BackendUserController->initializeView()` changed visibility from public to protected and should not be called any longer.

Additionally, first constructor argument :php:`$fileListController` of class
:php:`TYPO3\CMS\Filelist\FileList` is now optional, class property :php:`$fileListController`
should not be used any longer in hooks of that class.


Impact
======

Calling one of the above properties or methods from a third party object will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Controllers of the core are usually not called by extensions directly, but only through core routing and
dispatching mechanisms. Extensions are unlikely to be affected by this change.


Migration
=========

No migration possible.

.. index:: Backend, PHP-API, NotScanned, ext:filelist