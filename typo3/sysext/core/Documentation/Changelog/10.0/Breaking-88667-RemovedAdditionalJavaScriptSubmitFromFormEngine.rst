.. include:: /Includes.rst.txt

=====================================================================
Breaking: #88667 - Removed additionalJavaScriptSubmit from FormEngine
=====================================================================

See :issue:`88667`

Description
===========

FormEngine had the feature to add additional submit handlers via the option :php:`additionalJavaScriptSubmit`, that can
be set by form element renderables. TYPO3 uses RequireJS and a rewritten FormEngine since version 7, the property
:php:`additionalJavaScriptSubmit` has been removed.

Additional, functions of :js:`TBE_EDITOR` that are associated with that feature (namely :js:`addActionChecks`) were removed as well.


Impact
======

The option has no effect anymore, the code won't get executed at all.


Affected Installations
======================

All 3rd-party extensions using this option are affected.


Migration
=========

It is possible to create and register an AMD module.

.. code-block:: php

   $resultArray['requireJsModules'][] = 'TYPO3/CMS/MyExtension/SubmitHandler';


.. code-block:: javascript

   // typo3conf/ext/my_extension/Resources/Public/JavaScript/SubmitHandler.js
   define(['TYPO3/CMS/Backend/DocumentSaveActions'], function (DocumentSaveActions) {
     DocumentSaveActions.getInstance().addPreSubmitCallback(function (e) {
       // e is the submit event
       // Do stuff here

       // e.stopPropagation() stops the execution chain
     });
   });


.. index:: Backend, JavaScript, PHP-API, NotScanned, ext:backend
