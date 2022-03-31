.. include:: /Includes.rst.txt

==================================================================
Feature: #93426 - SVG-based Tree for Folder Navigation with Filter
==================================================================

See :issue:`93426`

Description
===========

The "File" module area (with the "File List" module) has a completely
rewritten Navigation Component called :html:`FileStorageTree`.

This Navigation Component is based on the same functionality
as the Page Tree - a SVG-based tree - and also offers lazy loading
of multiple nesting levels.

The previous implementation was based on an iframe with much
effort to load pure HTML instead of using SVGs. Since the file list component
was the last occurrence of using the iframe technology for Navigation
Components, this functionality will be marked as deprecated in later TYPO3 v11 releases.

The main benefit of the Folder Navigation based on the SVG tree is the enhanced
loading functionality. This way, the Folder Navigation has the exact same
look&feel as the Page Tree, and also now contains a always-enabled filter
on top of the Component, just as the Page Tree Navigation Component.


Impact
======

The navigation state of the component is stored similarly
to the Page Tree as both components benefit from sharing code.

The filter inside the Folder Navigation allows to search
for a folder or storage name, and even file names (no search through meta-data).
Users can filter for e.g. ".pdf" to show all available folders where
PDF files are stored.

Extension Authors who want to use a file-related navigation component in
their own extension can do this by specifying the :php:`navigationComponentId`

.. code-block:: php

   \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
       'random',
       'filerelatedmodule',
       'top',
       null,
       [
           'navigationComponentId' => 'TYPO3/CMS/Backend/Tree/FileStorageTreeContainer',
           'routeTarget' => \MyVendor\MyExtension\Controller\FileRelatedController::class . '::indexAction',
           'access' => 'user,group',
           'name' => 'myext_file',
           'icon' => 'EXT:myextension/Resources/Public/Icons/module-file-related.svg',
           'labels' => 'LLL:EXT:myextension/Resources/Private/Language/Modules/file_related.xlf'
       ]
   );

.. index:: Backend, ext:backend
