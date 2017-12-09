.. include:: ../../Includes.txt

==============================================
Breaking: #82426 - ExtJS and ExtDirect removal
==============================================

See :issue:`82426`

Description
===========

ExtJS and ExtDirect support has been removed from the core (TYPO3 Backend).
ExtJS Javascript is not loaded now in TYPO3 Backend.
ExtDirect classes were removed without substitution.


Removed classes:
----------------

* :php:`TYPO3\CMS\Backend\Tree\ExtDirectNode`
* :php:`TYPO3\CMS\Backend\Tree\Pagetree\Commands`
* :php:`TYPO3\CMS\Backend\Tree\Pagetree\DataProvider`
* :php:`TYPO3\CMS\Backend\Tree\Pagetree\ExtdirectTreeCommands`
* :php:`TYPO3\CMS\Backend\Tree\Pagetree\ExtdirectTreeDataProvider`
* :php:`TYPO3\CMS\Backend\Tree\Renderer\ExtJsJsonTreeRenderer`
* :php:`TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode`
* :php:`TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection`
* :php:`TYPO3\CMS\Core\ExtDirect\ExtDirectApi`
* :php:`TYPO3\CMS\Core\ExtDirect\ExtDirectRouter`
* :php:`TYPO3\CMS\Workspaces\Hooks\PagetreeCollectionsProcessor`


Removed methods:
----------------

* :php:`TYPO3\CMS\Backend\Tree\Pagetree\ExtdirectTreeDataProvider->getNodeTypes()`
* :php:`TYPO3\CMS\Backend\Tree\Pagetree\ExtdirectTreeDataProvider->loadResources()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->setExtJsPath()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getExtJsPath()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->addExtOnReadyCode()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->addExtDirectCode()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->loadExtJS()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->enableExtJsDebug()`


Removed interfaces:
-------------------

* :php:`TYPO3\CMS\Backend\Tree\Pagetree\CollectionProcessorInterface`
* :php:`TYPO3\CMS\Backend\Tree\EditableNodeLabelInterface`
* :php:`TYPO3\CMS\Backend\Tree\DraggableAndDropableNodeInterface`

Impact
======

JS code relying on ExtJS will stop working.
PHP code relying on ExtDirect classes being available will now throw a fatal error.


Affected Installations
======================

All installations having extensions relying on ExtJS being loaded or using ExtDirect API.


Migration
=========

JS code relying on ExtJS has to be reworked to not use ExtJS or to load ExtJS from custom extension.
PHP code related to ExtDirect should be changed to regular Backend AJAX routing.

.. index:: Backend, JavaScript, PHP-API, PartiallyScanned
