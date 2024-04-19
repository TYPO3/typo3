.. include:: /Includes.rst.txt

.. _deprecation-103528-1712153304:

==============================================================
Deprecation: #103528 - Deprecated `DocumentSaveActions` module
==============================================================

See :issue:`103528`

Description
===========

The JavaScript module :js:`@typo3/backend/document-save-actions.js` was
introduced in TYPO3 v7 to add some interactivity in FormEngine context.
At first it was only used to disable the submit button and render a
spinner icon instead. Over the course of some years, the module got more
functionality, for example to prevent saving when validation fails.

Since some refactorings within FormEngine, the module rather became a
burden. This became visible with the introduction of the
:ref:`Hotkeys API <feature-101507-1690808401>`, as
the :js:`@typo3/backend/document-save-actions.js` reacts on explicit :js:`click`
events on the save icon, that is not triggered when FormEngine invokes a
:ref:`save action via keyboard shortcuts <feature-103529-1712154338>`.
Adjusting :js:`document-save-actions.js`'s
behavior is necessary, but would become a breaking change, which is
unacceptable after the 13.0 release. For this reason, said module has
been marked as deprecated and its usages are replaced by its successor
:js:`@typo3/backend/form/submit-interceptor.js`.


Impact
======

Using the JavaScript module :js:`@typo3/backend/document-save-actions.js` will
render a deprecation warning in the browser's console.


Affected installations
======================

All installations relying on :js:`@typo3/backend/document-save-actions.js` are
affected.


Migration
=========

To migrate the interception of submit events, the successor module
:js:`@typo3/backend/form/submit-interceptor.js` shall be used instead.

The usage is similar to :js:`@typo3/backend/document-save-actions.js`, but
requires the form HTML element in its constructor.

Example
-------

..  code-block:: js

    import '@typo3/backend/form/submit-interceptor.js';

    // ...

    const formElement = document.querySelector('form');
    const submitInterceptor = new SubmitInterceptor(formElement);
    submitInterceptor.addPreSubmitCallback(function() {
        // the same handling as in @typo3/backend/document-save-actions.js
    });

.. index:: Backend, JavaScript, NotScanned, ext:backend
