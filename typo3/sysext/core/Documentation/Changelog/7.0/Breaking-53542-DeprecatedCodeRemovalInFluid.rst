
.. include:: /Includes.rst.txt

=============================================================
Breaking: #53542 - Removal of deprecated code in sysext fluid
=============================================================

See :issue:`53542`

Description
===========

ContainerViewHelper
-------------------

The following options have been removed from the ViewHelper:

* `enableJumpToUrl`
* `addCssFile`, use `includeCssFiles` instead
* `addJsFile`, use `includeJsFiles` instead


AbstractBackendViewHelper
-------------------------

The usage of `$GLOBALS['SOBE']` is removed for retrieving the DocumentTemplate instance.
Use `->getDocInstance()` instead.


TemplateView
------------

The following methods have been removed:

* `getTemplateRootPath()` is removed, use `getTemplateRootPaths()` instead
* `getPartialRootPath()` is removed, use `setPartialRootPaths()` instead
* `getLayoutRootPath()` is removed, use `getLayoutRootPaths()` instead


Impact
======

A call to any of the aforementioned methods by third party code will result in a fatal PHP error.


Affected installations
======================

Any installation which contains third party code still using these deprecated methods.


Migration
=========

Replace the calls with the suggestions outlined above.


.. index:: PHP-API, Fluid
