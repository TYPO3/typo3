.. include:: /Includes.rst.txt

=============================================================================
Feature: #85918 - Hide in menu / Show in menu entry for pages in context menu
=============================================================================

See :issue:`85918`

Description
===========

A new entry has been added to the context menu. It enables editors to toggle the
`hide in menu` / `show in menu` flag without opening page properties.

Find it as a child entry of `More Actions`.

Removing the entry from the menu is possible via User TSconfig with the following setting:

:typoscript:`options.contextMenu.table.pages.tree.disableItems = hideInMenus,showInMenus`


Impact
======

Editors will save some clicks when arranging menu structures.

.. index:: Backend, ext:backend
