.. include:: ../../Includes.txt

==================================================================================
Deprecation: #83806 - Deprecate page.javascriptLibs and page.javascriptLibs.jQuery
==================================================================================

See :issue:`83806`

Description
===========

The settings :typoscript:`page.javascriptLibs` and :typoscript:`page.javascriptLibs.jQuery` have been marked as
deprecated and will be removed in CMS 10.


Impact
======

Installations that use :typoscript:`page.javascriptLibs` or :typoscript:`page.javascriptLibs.jQuery` will trigger a
deprecation warning.


Affected Installations
======================

All installations that use one of the above settings.


Migration
=========

Use one of the following settings to add jQuery:

* :typoscript:`page.includeJSLibs`
* :typoscript:`page.includeJSFooterlibs`
* :typoscript:`page.includeJS`
* :typoscript:`page.includeJSFooter`
* :typoscript:`page.headerData`
* :typoscript:`page.footerData`

.. index:: Frontend, TypoScript, NotScanned
