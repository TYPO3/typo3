.. include:: /Includes.rst.txt

======================================
Deprecation: #89215 - jQuery.clearable
======================================

See :issue:`89215`

Description
===========

The jQuery plugin :js:`jquery.clearable` that provides a button to clear an input field has been marked as deprecated.


Impact
======

Using :js:`jquery.clearable` will trigger a deprecation warning in the browser's console.


Affected Installations
======================

All 3rd party extensions using :js:`jquery.clearable` are affected.


Migration
=========

Import the module :js:`TYPO3/CMS/Backend/Input/Clearable` and use the method :js:`clearable()` on a native :js:`HTMLInputElement`.

Example code:

.. code-block:: js

   require(['TYPO3/CMS/Backend/Input/Clearable'], function() {
     const inputField = document.querySelector('#some-input');
     if (inputField !== null) {
       inputField.clearable();
     }

     const clearables = Array.from(document.querySelectorAll('.t3js-clearable')).filter(inputElement => {
       // Filter input fields being a date time picker and a color picker
       return !inputElement.classList.contains('t3js-datetimepicker') && !inputElement.classList.contains('t3js-color-picker');
     });
     clearables.forEach(clearableField => clearableField.clearable());
  });

The method also accepts an :js:`options` object, allowing to set a :js:`onClear` callback. The callback receives the input field as an argument the clearing was applied to.

Example code:

.. code-block:: js

   const inputField = document.querySelector('#some-input');
   if (inputField !== null) {
     inputField.clearable({
       onClear: function (input) {
         input.closest('form').submit();
       }
     });
   }

.. index:: Backend, JavaScript, NotScanned, ext:backend
