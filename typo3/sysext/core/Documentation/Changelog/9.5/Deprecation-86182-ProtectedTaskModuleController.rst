.. include:: ../../Includes.txt

====================================================
Deprecation: #86182 - Protected TaskModuleController
====================================================

See :issue:`86182`

Description
===========

Class :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController` no longer inherits
:php:`TYPO3\CMS\Backend\Module\BaseScriptClass`.

Single task classes should no longer expect to have an instance of the :php:`TaskModuleController`
set as :php:`$GLOBALS['SOBE']`.

The following properties of class :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController`
changed their visibility from public to protected and should not be called any longer:

* [not scanned] :php:`MCONF`
* [not scanned] :php:`id`
* [not scanned] :php:`MOD_MENU`
* [not scanned] :php:`modMenu_type`
* [not scanned] :php:`modMenu_setDefaultList`
* [not scanned] :php:`modMenu_dontValidateList`
* [not scanned] :php:`content`
* [not scanned] :php:`perms_clause`
* [not scanned] :php:`CMD`
* [not scanned] :php:`extClassConf`
* [not scanned] :php:`extObj`

The following properties of class :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController`
changed their visibility from public to protected and should not be called any longer:

* [not scanned] :php:`menuConfig`
* [not scanned] :php:`mergeExternalItems`
* [not scanned] :php:`handleExternalFunctionValue`
* [not scanned] :php:`getExternalItemConfig`
* [not scanned] :php:`main`
* :php:`urlInIframe`
* [not scanned] :php:`extObjHeader`
* [not scanned] :php:`checkSubExtObj`
* [not scanned] :php:`checkExtObj`
* [not scanned] :php:`extObjContent`
* [not scanned] :php:`getExtObjContent`

Impact
======

Calling one of the above methods from an external object will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Most methods and properties are used internally in the :php:`TaskModuleController` only.
Instances with extensions delivering additional tasks for the
taskcenter may be affected.


Migration
=========

Single task should no longer rely on having an instance of :php:`TaskModuleController` set as
:php:`$GLOBALS['SOBE']`, an instance of the object is given as first constructor argument.

Properties and methods that have been set to protected should be calculated internally instead.


.. index:: Backend, PHP-API, PartiallyScanned, ext:taskcenter
