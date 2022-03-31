.. include:: /Includes.rst.txt

===========================================================
Feature: #84216 - New attribute "debug" in RenderViewHelper
===========================================================

See :issue:`84216`

Description
===========

The new attribute `debug` has been added to the RenderViewHelper which is `true` by default.
Setting this attribute to `false` disables the debug information rendered in the frontend if the fluid debug mode is
enabled in the admin panel.

Impact
======

It is now possible to disable the debug output in some specials cases like in the admin panel.

.. index:: Fluid, Frontend, ext:fluid
