.. include:: /Includes.rst.txt

===========================================================
Deprecation: #91606 - Global Datetime Picker initialization
===========================================================

See :issue:`91606`

Description
===========

Initializing all datetime pickers at once by invoking
:js:`DateTimePicker.initialize()` without passing an element has been marked as
deprecated.


Impact
======

Initializing all datetime pickers at once will trigger a deprecation warning in
the browser's console.


Affected Installations
======================

All 3rd party extensions calling :js:`DateTimePicker.initialize()` without any
arguments are affected.


Migration
=========

Initialize the datetime picker by passing an input element to the
:js:`.initialize()` method.

.. index:: Backend, JavaScript, NotScanned, ext:backend
