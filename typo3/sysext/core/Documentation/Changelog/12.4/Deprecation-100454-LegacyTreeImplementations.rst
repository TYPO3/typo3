.. include:: /Includes.rst.txt

.. _deprecation-100454-1680685413:

==================================================
Deprecation: #100454 - Legacy tree implementations
==================================================

See :issue:`100454`

Description
===========

Due to many refactorings in TYPO3's tree implementations in the past versions,
many implementations and functionality of the legacy rendering :php:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView`
is not needed anymore.

The following PHP classes are not in use anymore and have been marked as deprecated:

* :php:`\TYPO3\CMS\Backend\Tree\View\BrowseTreeView`
* :php:`\TYPO3\CMS\Backend\Tree\View\ElementBrowserPageTreeView`

The base class is still available, but discouraged to be used or extended,
even though TYPO3 still uses this in a few places.

The following properties and methods within the base class
:php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView` have either been marked as deprecated or
declared as internal:

* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->thisScript`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->BE_USER`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->clause`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->title`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->table`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->parentField`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->orderByFields`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->fieldArray`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->defaultList`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->determineScriptUrl()`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getThisScript()`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->PM_ATagWrap()`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->addTagAttributes()`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getRootIcon()`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getIcon()`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getRootRecord()`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getTitleStr()`
* :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getTitleAttrib()`


Impact
======

Instantiating the deprecated classes or calling the deprecated methods will
trigger a PHP deprecation warning, except for
:php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getThisScript()`, which is still used internally by
deprecated code.

The Extension Scanner will find those usages and additionally also reports
usages of the corresponding public properties of the :php-short:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView`
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
