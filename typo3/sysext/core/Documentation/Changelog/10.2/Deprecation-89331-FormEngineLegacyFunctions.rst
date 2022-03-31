.. include:: /Includes.rst.txt

=================================================
Deprecation: #89331 - FormEngine legacy functions
=================================================

See :issue:`89331`

Description
===========

The FormEngine supports global callback functions executed on certain interactions. Such functions were overridden and
spread through some extensions which are not related to FormEngine at all.

These functions have been marked as deprecated:

* :js:`setFormValueOpenBrowser()`
* :js:`setFormValueFromBrowseWin()`
* :js:`setHiddenFromList()`
* :js:`setFormValueManipulate()`
* :js:`setFormValue_getFObj()`

The function :js:`setFormValueFromBrowseWin()` is also called by `ElementBrowser`. Extensions not related to FormEngine
are able to override this function and inject custom handling. This approach has been marked as deprecated as well.


Impact
======

Calling a deprecated function will trigger a warning in the browser console.


Affected Installations
======================

All installations using 3rd party extensions calling any of these deprecated functions are affected.


Migration
=========

Some functions can be used in FormEngine context only from now on. Load the module `TYPO3/CMS/Backend/FormEngine` and
use the according replacements:

* :js:`setFormValueOpenBrowser()` - use :js:`FormEngine.openPopupWindow()` instead
* :js:`setFormValueFromBrowseWin()` - use :js:`FormEngine.setSelectOptionFromExternalSource()` instead
* :js:`setHiddenFromList()` - use :js:`FormEngine.updateHiddenFieldValueFromSelect()` instead
* :js:`setFormValueManipulate()` - no replacement, this is internal logic for form controls separated into according modules
* :js:`setFormValue_getFObj()` - use :js:`FormEngine.getFormElement()` instead

If :js:`setFormValueFromBrowseWin()` is not used within a FormEngine context, it is possible to listen to the
:js:`message` event.

Example code:

.. code-block:: js

   require(['TYPO3/CMS/Backend/Utility/MessageUtility'], function (MessageUtility) {
     window.addEventListener('message', function (e) {
       // MessageUtility.MessageUtility is correct as this is not an AMD module
       if (!MessageUtility.MessageUtility.verifyOrigin(e.origin)) {
         throw 'Denied message sent by ' + e.origin;
       }

       if (typeof e.data.fieldName === 'undefined') {
         throw 'fieldName not defined in message';
       }

       if (typeof e.data.value === 'undefined') {
         throw 'value not defined in message';
       }

       const result = e.data.value.split('_');
       const field = <HTMLInputElement>document.querySelector('input[name="' + e.data.fieldName + '"]');
       field.value = result[1];
     });
   }

.. index:: Backend, JavaScript, NotScanned, ext:backend
