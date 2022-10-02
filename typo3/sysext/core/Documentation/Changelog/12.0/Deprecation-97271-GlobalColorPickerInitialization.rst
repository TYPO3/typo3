.. include:: /Includes.rst.txt

.. _deprecation-97271:

========================================================
Deprecation: #97271 - Global Color Picker initialization
========================================================

See :issue:`97271`

Description
===========

Initializing all color pickers (via the :html:`t3js-color-picker` class) at
once by invoking :js:`ColorPicker.initialize()` without passing an element
has been marked as deprecated.

Impact
======

Initializing all datetime pickers at once will trigger a deprecation
warning in the browser's console.

Affected Installations
======================

All 3rd party extensions calling :js:`ColorPicker.initialize()` without any
arguments are affected.

Migration
=========

Initialize the color picker by passing an :js:`HTMLElement` to the
:js:`ColorPicker.initialize()` method.

.. index:: Backend, JavaScript, NotScanned, ext:backend
