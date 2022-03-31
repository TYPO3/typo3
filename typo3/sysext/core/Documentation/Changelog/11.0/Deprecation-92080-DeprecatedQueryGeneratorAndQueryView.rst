.. include:: /Includes.rst.txt

==================================================
Deprecation: #92080 - QueryGenerator and QueryView
==================================================

See :issue:`92080`
See :issue:`92129`

Description
===========

The classes :php:`TYPO3\CMS\Core\Database\QueryGenerator` and
:php:`TYPO3\CMS\Core\Database\QueryView` have been marked as deprecated.


Impact
======

Using the classes will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Both classes have been used within the backend only, the method
:php:`getTreelist()` has been used occasionally by backend extensions to recursively
fetch children of pages. Even if they are quite inflexible, some extensions may rely
on them. The extension scanner will find class usages with a strong match.


Migration
=========

As most simple solutions, the :php:`getTreeList` method could be copied over to an own extension.

.. index:: Backend, PHP-API, FullyScanned, ext:core
