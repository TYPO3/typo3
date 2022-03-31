.. include:: /Includes.rst.txt

===============================================
Deprecation: #93975 - TBE_EDITOR.fieldChanged()
===============================================

See :issue:`93975`

Description
===========

The JavaScript function :js:`TBE_EDITOR.fieldChanged()` is a precursor of the
rewritten FormEngine that started with TYPO3 v7 already.
Now, FormEngine has proper change handling which renders the function
:js:`TBE_EDITOR.fieldChanged()` obsolete, thus this function became marked as
deprecated.


Impact
======

Using :js:`TBE_EDITOR.fieldChanged()` will trigger a deprecation entry in the
browser's console.


Affected Installations
======================

Every installation with 3rd-party extensions installed using this function is
affected.


Migration
=========

It is possible to trigger the :js:`change` event on the given field, if
FormEngine is unable to detect changes automatically.

Example:

.. code-block:: javascript

   // Previous invocation
   TBE_EDITOR.fieldChanged('table', 'field_name', 42);

   // Migrate to event-based handling
   document
     .querySelector('[name="data[table][field_name][42]"]')
     .dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));

.. index:: Backend, JavaScript, NotScanned, ext:backend
