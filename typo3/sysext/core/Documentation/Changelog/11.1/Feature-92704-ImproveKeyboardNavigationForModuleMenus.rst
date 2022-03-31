.. include:: /Includes.rst.txt

==============================================================
Feature: #92704 - Improve keyboard navigation for module menus
==============================================================

See :issue:`92704`

Description
===========

The module menu implements the keyboard navigation suggested
by the ARIA Best Practices 1.1 for roles :html:`menubar` and :html:`menu`.
The first level menu has a :html:`menubar` role, the second level
submenus have a :html:`menu` role. The buttons have the :html:`menuitem`
role. Both the :html:`menubar` and the :html:`menu` are oriented
vertically for assistive technology matching the visual
representation which affects the keyboard navigation.

Space/Enter shows the module unless the item has a submenu.
Space/Enter and Right Arrow open a submenu and move focus to
the first item.

Up/Down Arrow and Home/End navigate within the current
level of the menu.
Ctrl + Home/End navigate within the first level of the menu
(extension of the ARIA pattern).

Left/Right Arrow moves to the parent items predecessor/successor
when on a submodule item. The submenu will not be closed
(deviation from the ARIA pattern).

Escape moves to the parent item of a submodule item.
The submenu will not be closed (deviation from the ARIA pattern).

Tab and Shift + Tab move to the next item outside of the
module menu.

The help menu implements the keyboard navigation suggested
by the ARIA Best Practices 1.1 for the role :html:`menu`. This
is the same as the module menu but limited to a single level.


Impact
======

The main module menu and the help menu are now usable with keyboard alone.
This includes users that access the backend with a screen reader or other
assistive technology.

.. index:: Backend, ext:backend
