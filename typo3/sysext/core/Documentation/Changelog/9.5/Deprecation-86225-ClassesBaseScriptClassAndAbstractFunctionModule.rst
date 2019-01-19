.. include:: ../../Includes.txt

========================================================================
Deprecation: #86225 - Classes BaseScriptClass and AbstractFunctionModule
========================================================================

See :issue:`86225`

Description
===========

The two classes :php:`TYPO3\CMS\Backend\Module\BaseScriptClass`, also known as
:php:`t3lib_SCbase` and :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule`,
also known as :php:`t3lib_extobjbase` have been marked as deprecated and will be removed
in TYPO3 v10.


Impact
======

Using one of the classes will trigger a :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

The :php:`BaseScriptClass` has been commonly extended by extensions that add own
backend modules and did not rely on extbase for that. There is nothing wrong
with not using extbase, but most of the methods from :php:`BaseScriptClass`
were unused by own extensions and hard to understand, too.

Class :php:`AbstractFunctionModule` has been extended by some extensions that
add own sub modules to the Page -> Info or the Page -> Template view.

The extension scanner will find possible usages.

Migration
=========

A migration is often relatively simple: Extensions that extend :php:`BaseScriptClass`
should verify which methods and properties are actually used from the parent class. The
most simple solution is to just copy those over to the own class and remove the
inheritance. It is good practice to at least change their visibility from :php:`public`
to :php:`protected` at the same time if possible.

Extensions that extend :php:`AbstractFunctionModule` should do the same. The main `info`
and `tstemplate` controllers typically only call the methods :php:`init()` and :php:`main()`
of those classes as entry points, those need to be kept public.


.. index:: Backend, PHP-API, FullyScanned
