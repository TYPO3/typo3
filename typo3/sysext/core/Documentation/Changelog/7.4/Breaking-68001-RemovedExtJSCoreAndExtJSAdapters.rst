
.. include:: /Includes.rst.txt

========================================================
Breaking: #68001 - Removed ExtJS Core and ExtJS Adapters
========================================================

See :issue:`68001`

Description
===========

The functionality of "ExtCore", which acts as a slim ExtJS base functionality similar to what libraries like underscore.js or jQuery do,
but is just dated, has been completely removed from the TYPO3 Core.

The custom adapters which have previously been shipped with ExtJS to allow jQuery functionality to be used
with ExtJS underneath have been removed without substitution. The adapters are not compatible with supported
jQuery, prototype.js or YUI versions anymore and their usages have been slower than ExtJS's base library natively.


Impact
======

Using TypoScript options `page.javascriptLibs.ExtCore = 1`, `page.javascriptLibs.ExtCore.debug = 1` and `page.javascriptLibs.ExtJs.adapter` have no effect anymore.

Using `<f:be.container>` ViewHelpers in a custom Backend module, setting the extJsAdapter, property will result in a fatal error.

Calling `$pageRenderer->loadExtJS()` with a custom third parameter will have no effect anymore.

Calling the methods `loadExtCore()`, `enableExtCoreDebug()`, `getExtCorePath()` and `setExtCorePath()` of `PageRenderer` will result in fatal errors.


Affected Installations
======================

Instances that use ExtCore in the TYPO3 Frontend

Extensions that use `<f:be.container>` with an ExtJS Adapter

Extensions that use the `PageRenderer` object directly to load custom ExtJS or ExtCore.


Migration
=========

Use alternatives for ExtCore or adapters in custom extensions.


.. index:: PHP-API, Fluid, TypoScript, JavaScript, Backend, Frontend
