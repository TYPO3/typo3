.. include:: /Includes.rst.txt

======================================================
Deprecation: #92132 - ViewHelper f:be.buttons.shortcut
======================================================

See :issue:`92132`

Description
===========

The Fluid ViewHelper `f:be.buttons.shortcut` has been marked as deprecated.

Additionally, the argument `getVars` of `ext:backend` related
ViewHelper `be:moduleLayout.button.shortcutButton` has been marked as deprecated.


Impact
======

Using ViewHelper `f:be.buttons.shortcut` and using argument `getVars` of
ViewHelper `be:moduleLayout.button.shortcutButton` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

The ViewHelpers are occasionally used in backend module context to render the
shortcut / bookmark icon in the doc header. Some custom backend extensions may be affected.


Migration
=========

Use `ext:backend` related ViewHelper `be:moduleLayout.button.shortcutButton`
with argument `arguments` instead, or use the :php:`ButtonBar->makeShortcutButton()` API in PHP directly.

.. index:: Fluid, NotScanned, ext:backend
