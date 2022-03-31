.. include:: /Includes.rst.txt

================================================================
Feature: #94474 - Improved show columns selection in record list
================================================================

See :issue:`94474`

Description
===========

Since :issue:`94218`, the column selector in the record list,
formerly known as "field selector", is available for each
individual record type in its table header. When accessing
the selector, a dropdown opened, displaying all available
columns.

This was already a huge improvement, as the selection was
now directly bound to the corresponding table and was
always available, not only in the "single-table view".

However, there were still some drawbacks, especially the fact
that the dropdown solution could lead to confusion in case a
record contains a couple of columns with long labels. Therefore,
the column selection has been improved and is now not longer
opened in a dropdown, but lives in a clear and large enough modal.

In the new modal, besides the columns to select, there are three
new options available:

*  Option to select all columns
*  Option to unselect all columns
*  Option to toggle (invert) the current selection

Those options are also fixed at the top, so they are always
visible, even for records with a lot of columns, for example `pages`.

Management fields, such as `uid` or `cr_date` are now displayed
with human-readable labels, making them more useful for editors.
Especially because those labels are not only used in the selector,
but are now also displayed in the record list table header.

Furthermore, the columns are now sorted lexically, while always
enabled columns, such as the record title, are always at the top
and all columns, not having a label, are added at the end of the list.

The checkboxes are improved in their size and appearance. Instead of
the usual "check" icon, an "eye" icon is used, making the intention
clear.

Impact
======

The column selector of each table in the record list now opens
a modal with improved selection functionality and an overall
improved UX.

.. index:: Backend, ext:recordlist
