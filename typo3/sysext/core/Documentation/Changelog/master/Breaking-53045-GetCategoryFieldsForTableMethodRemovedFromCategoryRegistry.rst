.. include:: ../../Includes.txt

===================================================================================
Breaking: #53045 - getCategoryFieldsForTable() method removed from CategoryRegistry
===================================================================================

See :issue:`53045`

Description
===========

The method :php:`getCategoryFieldsForTable()` is removed from the :php:`\TYPO3\CMS\Core\Category\CategoryRegistry`
class.

It could only handle the `tt_content` menus `categorized_pages` and `categorized_content`.


Impact
======

The method :php:`getCategoryFieldsForTable()` is removed. Any third party code that uses it will break.


Affected Installations
======================

All installations with third party code making using the removed method.


Migration
=========

A new method  :php:`getCategoryFieldItems()` is added that can be used by third party code for any
categorized table.

.. index:: Backend, PHP-API, TCA
