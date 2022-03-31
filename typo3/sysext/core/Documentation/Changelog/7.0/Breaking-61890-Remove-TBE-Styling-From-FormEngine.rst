
.. include:: /Includes.rst.txt

==============================================================
Breaking: #61890 - TBE Styling removed from FormEngine and TCA
==============================================================

See :issue:`61890`

Description
===========

The styling functionality of FormEngine was based on a mixture of loose variables within :code:`$GLOBALS['TBE_STYLES']`
overridden by hardcoded values in various Backend PHP classes. This setup, additionally mixed with CSS classes
that followed a very complicated syntax to render certain fields differently, has been removed in order to allow
Backend styling for FormEngine completely based on CSS/LESS.


Impact
======

Using the following CSS classes within FormEngine don't have any effect anymore:

* class-main
* class-main1
* class-main2
* class-main3
* class-main4
* class-main5
* class-main11
* class-main12
* class-main13
* class-main14
* class-main15
* class-main21
* class-main22
* class-main23
* class-main24
* class-main25
* class-main31
* class-main32
* class-main33
* class-main34
* class-main35
* class-main41
* class-main42
* class-main43
* class-main44
* class-main45
* class-main51
* class-main52
* class-main53
* class-main54
* class-main55
* wrapperTable
* wrapperTable1
* wrapperTable2
* wrapperTable3
* wrapperTable4
* wrapperTable5
* formField
* formField1
* formField2
* formField3
* formField4
* formField5

Additionally, the following keys of $TBE_STYLES have no effect anymore:

* $TBE_STYLES['colorschemes']
* $TBE_STYLES['styleschemes']
* $TBE_STYLES['borderschemes']

They can safely removed from any third party extension.

The 5th parameter defining custom styleschemes in any field defined in $TCA[mytable][types][mytype][showitem] or
$TCA[mytable][palettes][mypalette][showitem] has no effect anymore and can be removed from any third party extension
(e.g. myfield;mylabel;usedpalette;extraDefinition;stylescheme).

Any styling is now done solely via LESS.

Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the CSS classes for styling or dynamic HTML via JavaScript.


Migration
=========

Use the available CSS classes for custom styling and modifying FormEngine. Clean up any custom TCA definitions with a
stylescheme in 3rd party extensions, where the fifth parameter of a field definition in
$TCA[mytable][types][mytype][showitem] or $TCA[mytable][palettes][mypalette][showitem] is used.


.. index:: PHP-API, TCA, Backend
