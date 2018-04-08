.. include:: ../../Includes.txt

==============================================
Deprecation: #84145 - Deprecate ext_isLinkable
==============================================

See :issue:`84145`

Description
===========

The method :php:`TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView->ext_isLinkable()` has been marked as
deprecated. It always returned true and still does it until removed.


Impact
======

Little to no impact in extensions, the method behavior usually does not change.


Affected Installations
======================

Extensions extending the folder tree of the element browser may be affected but still should not change their behavior.
Extension scanner may find usages and marks them as weak match since the methods appears in other classes as well.


Migration
=========

Don't call :php:`ext_isLinkable()` anymore and assume :php:`true` as return value.

.. index:: Backend, PHP-API, FullyScanned