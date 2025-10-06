..  include:: /Includes.rst.txt

..  _feature-106637-1760521627:

========================================================
Feature: #106637 - Implement accessible combobox pattern
========================================================

See :issue:`106637`

Description
===========

A new ARIA 1.2 compliant combobox web component has been added that replaces the
legacy valuepicker select pattern. The implementation follows W3C accessibility
guidelines and includes comprehensive keyboard navigation support.

FormEngine elements including EmailElement, InputTextElement, and NumberElement
have been updated to use the new combobox component instead of the previous
valuepicker implementation. The link browser components have been adapted to use
the combobox pattern as well.


Impact
======

The component provides full keyboard navigation support with Arrow keys, Enter,
Tab, and Escape keys. It includes visual selection indicators with checkmarks
and a clear button for resetting the input value.

..  index:: Backend, ext:backend
