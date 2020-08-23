.. include:: ../../Includes.txt

====================================================================================
Deprecation: #92080 - Deprecated getTreeList() calls to QueryGenerator and QueryView
====================================================================================

See :issue:`92080`

Description
===========

The method :php:`getTreeList()` of classes :php:`TYPO3\CMS\Core\Database\QueryGenerator` and
:php:`TYPO3\CMS\Core\Database\QueryView` have been set to protected.


Impact
======

Using the methods will log a deprecation warning.


Affected Installations
======================

The methods have been used occasionally by backend extensions to recursively fetch children of
pages. Even if they are quite inflexible, some extensions may rely on them. The extension scanner
will find usages with a weak match, but usages should not be confused with a method of the same
name in :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getTreeList()`.


Migration
=========

As most simple solutions, the methods could be copied over to an own extension.

.. index:: Backend, PHP-API, FullyScanned, ext:core
