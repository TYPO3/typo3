.. include:: /Includes.rst.txt

====================================================================
Deprecation: #93944 - File Tree as iframe migrated to SVG-based tree
====================================================================

See :issue:`93944`

Description
===========

When registered backend modules have the legacy-tree navigation frame
:html:`file_navframe` set via the module configuration
:php:`navigationFrameModule`, the modules are now using the new SVG-based Folder
tree view as component (non-iFrame).


Impact
======

Modules still registering the old :html:`file_navframe` option via
Module Configuration :php:`navigationFrameModule` will automatically be migrated
to the new Component, and a PHP :php:`E_USER_DEPRECATED` error will be triggered.


Affected Installations
======================

TYPO3 installations with custom extensions having backend modules
using the filelist navigation frame (folder-based tree).

Modules that use the implicit main module configuration and are
located directly within the "File" module are not affected.


Migration
=========

Change the affected code in your ext_tables.php:

:php:`'navigationFrameModule' => 'file_navframe'`

to

:php:`'navigationComponentId' => 'TYPO3/CMS/Backend/Tree/FileStorageTreeContainer'`

.. index:: Backend, NotScanned, ext:backend
