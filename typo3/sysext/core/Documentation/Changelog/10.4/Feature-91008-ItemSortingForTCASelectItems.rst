.. include:: /Includes.rst.txt

===================================================
Feature: #91008 - Item sorting for TCA select items
===================================================

See :issue:`91008`

Description
===========

A new option :php:`sortOrders` for TCA-based select fields has been added to allow
sorting of static TCA select items by their values or labels.

This is now used in TYPO3 Core's :php:`tt_content.list_type` whereas
a previous :php:`itemProcFunc` was used to sort all plugins by label
in the FormEngine dropdown.

Built-in orderings are to sort items by their labels or values. It is also possible
to define custom :php:`sortOrders` via custom PHP code.

Examples from tt_contents' :php:`list_type` TCA:

.. code-block:: php

   // Sort all items by label ("asc" or "desc" is possible)
   $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['sortItems'] = [
       'label' => 'asc'
   ];

   // Sort all items by value ("asc" or "desc" is possible)
   $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['sortItems'] = [
       'value' => 'desc'
   ];

   // Sort all items by a custom function
   $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['sortItems'] = [
       'My_Extension' => 'ksort'
   ];

   $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['sortItems'] = [
       'My_Extension' => \VendorName\PackageName\TcaSorter::class . '->sortByMagic'
   ];

When using grouped select fields with "itemGroups", sorting happens on a
per-group basis - all items within one group are sorted - as the group ordering
is preserved.


Impact
======

Plugins in FormEngine are now using this option in TYPO3 Core, and other TCA
select fields can benefit from this as well.

This option is solely built for display purposes in FormEngine.

.. index:: TCA, ext:core
