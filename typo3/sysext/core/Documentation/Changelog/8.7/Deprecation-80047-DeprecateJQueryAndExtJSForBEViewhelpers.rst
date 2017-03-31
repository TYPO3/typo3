.. include:: ../../Includes.txt

===================================================================
Deprecation: #80047 - Deprecate jQuery and extJS for BE viewhelpers
===================================================================

See :issue:`80047`

Description
===========

The BE related ViewHelpers :html:`<f:be.container>` and :html:`<f:be.pageRenderer>` have properties to activate ExtJS
and jQuery with various options. The usage of ExtJS has been deprecated and jQuery is always loaded. Therefore the
following attributes have been marked as deprecated.

:html:`<f:be.container>`

- `enableClickMenu`
- `loadExtJs`
- `loadExtJsTheme`
- `enableExtJsDebug`
- `loadJQuery`
- `jQueryNamespace`

:html:`<f:be.pageRenderer>`

- `loadExtJs`
- `loadExtJsTheme`
- `enableExtJsDebug`
- `loadJQuery`
- `jQueryNamespace`


Impact
======

Using these attributes will trigger a deprecation log entry. Code using them will work until these methods are removed in TYPO3 v9.


Affected Installations
======================

Any installation using the mentioned attributes.


Migration
=========

Use ``includeRequireJsModules`` property of the :html:`<f:be.pageRenderer>` or :html:`<f:be.container>` ViewHelpers to add needed RequireJS modules.

Example:

.. code-block:: xml

   <f:be.pageRenderer
      includeRequireJsModules="{
         0:'TYPO3/CMS/Backend/ContextMenu'
      }"
   />


See also documentation about RequireJS documentation_

.. _documentation: https://docs.typo3.org/typo3cms/CoreApiReference/ApiOverview/JavaScript/RequireJS/Index.html

.. index:: Backend, Fluid
