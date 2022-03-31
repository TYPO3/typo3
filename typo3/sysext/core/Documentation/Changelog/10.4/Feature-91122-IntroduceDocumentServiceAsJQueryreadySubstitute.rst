.. include:: /Includes.rst.txt

======================================================================
Feature: #91122 - Introduce DocumentService as JQuery.ready substitute
======================================================================

See :issue:`91122`

Description
===========

The module :js:`TYPO3/CMS/Core/DocumentService` provides native JavaScript
functions to detect DOM ready-state returning a :js:`Promise<Document>`.

Internally the Promise is resolved when native :js:`DOMContentLoaded` event has
been emitted or when :js:`document.readyState` is defined already. It means
that initial HTML document has been completely loaded and parsed, without
waiting for stylesheets, images, and subframes to finish loading.


Impact
======

.. code-block:: javascript

   $(document).ready(() => {
     // your application code
   });

Above JQuery code can be transformed into the following using :js:`DocumentService`:

.. code-block:: javascript

   require(['TYPO3/CMS/Core/DocumentService'], function (DocumentService) {
     DocumentService.ready().then(() => {
       // your application code
     });
   });

.. index:: Backend, JavaScript, ext:core
