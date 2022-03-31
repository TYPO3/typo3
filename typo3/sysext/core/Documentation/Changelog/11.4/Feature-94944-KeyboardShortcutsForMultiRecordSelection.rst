.. include:: /Includes.rst.txt

===============================================================
Feature: #94944 - Keyboard shortcuts for multi record selection
===============================================================

See :issue:`94944`

Description
===========

To further increase the usability in the Backend, the multi record selection,
introduced with :issue:`94906`, has been extended for keyboard shortcuts.

The shortcuts can be used in every module, which implements the multi record
selection. You can recognize this by the dropdown menu in the first header
column of the record listing.

In such module, when clicking on a checkbox while holding the

* `shift` key: All records in the range of the last clicked checkbox and the current one are checked / unchecked

* `option` (macOS) or `ctrl` (Windows / Linux) key: The current selection is toggled (inverted)


Impact
======

The multi record selection now also features keyboard shortcuts to further
increase the usability of this component.

.. index:: Backend, ext:backend
