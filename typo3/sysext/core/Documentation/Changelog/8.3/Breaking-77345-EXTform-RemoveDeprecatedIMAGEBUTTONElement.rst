
.. include:: ../../Includes.txt

===================================================================
Breaking: #77345 - EXT:form - Remove deprecated IMAGEBUTTON element
===================================================================

See :issue:`77345`

Description
===========

The :ts:`IMAGEBUTTON` element has been removed in TYPO3 v8.


Impact
======

Using the :ts:`IMAGEBUTTON` element is not working anymore, i.e. no :ts:`IMAGEBUTTON` element will be rendered.


Affected Installations
======================

All installations using the :ts:`IMAGEBUTTON` element.


Migration
=========

Remove all usages of the :ts:`IMAGEBUTTON` element.

.. index:: TypoScript, ext:form
