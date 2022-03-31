.. include:: /Includes.rst.txt

=========================================
Feature: #90168 - Introduce Modal Actions
=========================================

See :issue:`90168`

Description
===========

Action buttons in modals created by the :js:`TYPO3/CMS/Backend/Modal` module may
now make use of :js:`TYPO3/CMS/Backend/ActionButton/ImmediateAction` and
:js:`TYPO3/CMS/Backend/ActionButton/DeferredAction`.

As an alternative to the existing :js:`trigger` option, the new option
:js:`action` may be used with an instance of the previously mentioned modules.

Example:

.. code-block:: js

   Modal.confirm('Header', 'Some content', Severity.error, [
     {
       text: 'Based on trigger()',
       trigger: function () {
         console.log('Vintage!');
       }
     },
     {
       text: 'Based on action',
       action: new DeferredAction(() => {
         return new AjaxRequest('/any/endpoint').post({});
       })
     }
   ]);


Impact
======

Activating any action disables all buttons in the modal. Once the action is
done, the modal disappears automatically.

Buttons of the type :js:`DeferredAction` render a spinner on activation into the
button.

.. index:: Backend, JavaScript, ext:backend
