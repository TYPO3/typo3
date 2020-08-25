.. include:: ../../Includes.txt

=============================================================
Deprecation: #92080 - Deprecated QueryGenerator and QueryView
=============================================================

See :issue:`92080`
See :issue:`92129`

Description
===========

The classes :php:`TYPO3\CMS\Core\Database\QueryGenerator` and
:php:`TYPO3\CMS\Core\Database\QueryView` have been deprecated.


Impact
======

Using the classes will log a deprecation warning.


Affected Installations
======================

Most of the classes have been used within the backend only, the methods
:php:`getTreelist()` have been used occasionally by backend extensions to recursively
fetch children of pages. Even if they are quite inflexible, some extensions may rely
on them. The extension scanner will find class usages with a strong match.


Migration
=========

As most simple solutions, the :php:`getTreeList` methods could be copied over to an own extension.

.. index:: Backend, PHP-API, FullyScanned, ext:core
