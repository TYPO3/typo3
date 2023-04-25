.. include:: /Includes.rst.txt

.. _deprecation-100454-1680685413:

==================================================
Deprecation: #100454 - Legacy tree implementations
==================================================

See :issue:`100454`

Description
===========

Due to many refactorings in TYPO3's tree implementations in the past versions,
many implementations and functionality of the legacy rendering :php:`\TYPO3\CMS\Backend\Tree\AbstractTreeView`
is not needed anymore.

The following PHP classes are not in use anymore and have been marked as deprecated:

* :php:`\TYPO3\CMS\Backend\Tree\View\BrowseTreeView`
* :php:`\TYPO3\CMS\Backend\Tree\View\ElementBrowserPageTreeView`

The base class is still available, but discouraged to be used or extended,
even though TYPO3 still uses this in a few places.

The following properties and methods within the base class
:php:`AbstractTreeView` have either been marked as deprecated or
declared as internal:

* :php:`AbstractTreeView->thisScript`
* :php:`AbstractTreeView->BE_USER`
* :php:`AbstractTreeView->clause`
* :php:`AbstractTreeView->title`
* :php:`AbstractTreeView->table`
* :php:`AbstractTreeView->parentField`
* :php:`AbstractTreeView->orderByFields`
* :php:`AbstractTreeView->fieldArray`
* :php:`AbstractTreeView->defaultList`
* :php:`AbstractTreeView->determineScriptUrl()`
* :php:`AbstractTreeView->getThisScript()`
* :php:`AbstractTreeView->PM_ATagWrap()`
* :php:`AbstractTreeView->addTagAttributes()`
* :php:`AbstractTreeView->getRootIcon()`
* :php:`AbstractTreeView->getIcon()`
* :php:`AbstractTreeView->getRootRecord()`
* :php:`AbstractTreeView->getTitleStr()`
* :php:`AbstractTreeView->getTitleAttrib()`


Impact
======

Instantiating the deprecated classes or calling the deprecated methods will
trigger a PHP deprecation warning, except for
:php:`AbstractTreeView->getThisScript()`, which is still used internally by
deprecated code.

The Extension Scanner will find those usages and additionally also reports
usages of the corresponding public properties of the :php:`AbstractTreeView`
class.


Affected installations
======================

TYPO3 installations with custom extensions using this functionality. This is
usually the case for old installations from TYPO3 v6 or TYPO3 v4 times.


Migration
=========

It is recommended to avoid generating the markup directly in PHP. Instead use
one of various other tree functionalities (for example, see PageTree implementations)
in PHP and render trees via web components or Fluid.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
