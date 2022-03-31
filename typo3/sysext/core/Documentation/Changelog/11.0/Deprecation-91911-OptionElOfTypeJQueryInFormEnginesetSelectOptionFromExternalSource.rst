.. include:: /Includes.rst.txt

=============================================================================================
Deprecation: #91911 - optionEl of type jQuery in FormEngine.setSelectOptionFromExternalSource
=============================================================================================

See :issue:`91911`

Description
===========

The 6th argument :js:`optionEl` of the method
:js:`FormEngine.setSelectOptionFromExternalSource()` now accepts objects of type
`HTMLOptionElement`.

In the same run, passing a jQuery object has been marked as deprecated.


Impact
======

jQuery objects automatically get converted to their native HTMLElement object.
Calling the method with passing a jQuery object will log a deprecation warning
to the browser's console.


Affected Installations
======================

All installations passing a jQuery object as :js:`optionEl` to
:js:`FormEngine.setSelectOptionFromExternalSource()` are affected.


Migration
=========

Pass a native HTMLOptionElement to
:js:`FormEngine.setSelectOptionFromExternalSource()`.

.. index:: Backend, JavaScript, NotScanned, ext:backend
