.. include:: /Includes.rst.txt

======================================================
Feature: #89496: Make context menu usable via keyboard
======================================================

See :issue:`89496`

Description
===========

The context menus are now usable via keyboard. Pressing Shift+F10
will open the context menu for the focused element. It is also possible to use arrows, home and end keys
in order to navigate through the menu. Besides that, using enter and
space keys will active items or open submenus.

This change follows the best practices as described in WAI-ARIA Authoring Practices 1.1,
see the `W3 document`_ for further reading.

.. _W3 document: https://www.w3.org/TR/wai-aria-practices-1.1/#keyboard-interaction-12

Impact
======

Added :html:`tabindex`, :html:`role`, and :html:`aria-*` attributes to context menus
as advised in WAI-ARIA Authoring Practices 1.1. Screen readers are now
able to recognize the context menu properly.

.. index:: Backend, JavaScript, ext:backend
