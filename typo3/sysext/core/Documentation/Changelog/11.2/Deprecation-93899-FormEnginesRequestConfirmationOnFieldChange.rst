.. include:: /Includes.rst.txt

===================================================================
Deprecation: #93899 - FormEngine's requestConfirmationOnFieldChange
===================================================================

See :issue:`93899`

Description
===========

FormEngine's JavaScript method :js:`FormEngine.requestConfirmationOnFieldChange()`
to register and trigger the update request when a field's value changes, has
been marked as deprecated.


Impact
======

Calling the method :js:`FormEngine.requestConfirmationOnFieldChange()` will
trigger a deprecation warning in the browser's console.


Affected Installations
======================

Any 3rd party extension using the aforementioned method is affected.


Migration
=========

There is no migration available. If a field is properly configured to update the
FormEngine after updating a field, a LitElement is rendered into DOM which will
handle the update request.

.. index:: Backend, JavaScript, NotScanned, ext:backend
