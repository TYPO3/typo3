
.. include:: ../../Includes.txt

===================================================================
Breaking: #77345 - EXT:form - Remove deprecated IMAGEBUTTON element
===================================================================

See :issue:`77345`

Description
===========

The `IMAGEBUTTON` element has been removed in TYPO3 v8.


Impact
======

Using the `IMAGEBUTTON` element is not working anymore, i.e. no `IMAGEBUTTON` element will be rendered.


Affected Installations
======================

All installations using the `IMAGEBUTTON` element.


Migration
=========

Remove all usages of the `IMAGEBUTTON` element.

.. index:: TypoScript, ext:form
