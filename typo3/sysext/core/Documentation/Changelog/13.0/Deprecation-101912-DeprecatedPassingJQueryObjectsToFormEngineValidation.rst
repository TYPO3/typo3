.. include:: /Includes.rst.txt

.. _deprecation-101912-1694611003:

======================================================================
Deprecation: #101912 - Passing jQuery objects to FormEngine validation
======================================================================

See :issue:`101912`

Description
===========

Both methods, :js:`validateField()` and :js:`markFieldAsChanged()` accept a form
field as argument that is either of type :js:`HTMLInputElement`,
:js:`HTMLSelectElement`, :js:`HTMLTextareaElement`, or :js:`jQuery`.
Passing all of the aforementioned types is supported since TYPO3 v11, therefore,
passing a jQuery object has been deprecated.


Impact
======

Calling any method, :js:`validateField()` or :js:`markFieldAsChanged()` with
passing jQuery-based objects will render a warning in the browser console, along
with a stacktrace to help identifying the caller code.


Affected installations
======================

All third-party extensions using the deprecated methods of the
:js:`@typo3/backend/form-engine-validation` module are affected.


Migration
=========

Do not pass jQuery-based objects into the deprecated methods. Consider migrating
away from jQuery at all, or use :js:`$field.get(0)` as interim solution.

.. index:: Backend, JavaScript, NotScanned, ext:backend
