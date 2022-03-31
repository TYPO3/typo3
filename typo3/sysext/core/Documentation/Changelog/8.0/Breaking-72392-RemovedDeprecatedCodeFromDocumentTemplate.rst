
.. include:: /Includes.rst.txt

================================================================
Breaking: #72392 - Removed deprecated code from DocumentTemplate
================================================================

See :issue:`72392`

Description
===========

Remove deprecated code from DocumentTemplate

The following properties have been removed:

`JScodeLibArray`
`docType`
`inDocStyles`
`endJS`
`bgColor`
`bgColor2`
`bgColor3`
`bgColor4`
`bgColor5`
`bgColor6`
`hoverColor`
`backGroundImage`
`inDocStyles_TBEstyle`
`parseTimeFlag`
`charset`

The following methods have been removed:

`getPageRenderer()`
`wrapClickMenuOnIcon()`
`issueCommand()`
`formatTime()`
`parseTime()`
`spacer()`
`endPageJS()`
`dfw()`
`rfw()`
`table()`
`menuTable()`
`getDynamicTabMenu()`
`getDynTabMenu()`
`getDynTabMenuId()`
`collapseableSection()`


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use calls to any of the above mentioned methods.


Migration
=========

For `wrapClickMenuOnIcon()` use `BackendUtility::wrapClickMenuOnIcon()` instead.
For `issueCommand()` use `BackendUtility::getLinkToDataHandlerAction()` instead.
For `formatTime()` and `parseTime()` use the corresponding methods in BackendUtility.
For `rfw()` and `dfw()` use proper HTML directly instead.
For `getDynamicTabMenu()` use `getDynamicTabMenu()` from ModuleTemplate instead.
For `collapseableSection()` use HTML bootstrap classes, localStorage etc.

.. index:: PHP-API, Backend
