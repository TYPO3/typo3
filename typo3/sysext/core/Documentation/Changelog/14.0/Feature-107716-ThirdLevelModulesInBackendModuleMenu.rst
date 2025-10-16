..  include:: /Includes.rst.txt

..  _feature-107716-1739750400:

=============================================================
Feature: #107716 - Third-level modules in backend module menu
=============================================================

See :issue:`107716`

Description
===========

The backend module menu now supports display of three levels of module
nesting, improving navigation for complex module structures.

Previously, the module menu only displayed two levels: main modules
and their direct submodules. With this feature, submodules can now
have their own child modules (third level), which are displayed in
the menu with proper visual hierarchy.

Third-level modules are displayed without icons and with increased
indentation to distinguish them from their parent modules. Users can
expand or collapse second-level modules that have third-level children
by clicking on the chevron indicator next to the module name.

The module link itself remains clickable to navigate to the module,
while the indicator toggles the visibility of child modules.

The new level is also fully accessible via keyboard.

Impact
======

Extension developers can now create deeper module hierarchies with up
to three levels of nesting, allowing for better organization of complex
module structures. The expand/collapse functionality is fully keyboard
accessible and provides a cleaner, more organized navigation experience.

..  index:: Backend, ext:backend
