
.. include:: /Includes.rst.txt

===================================================================
Breaking: #77345 - EXT:form - Remove deprecated IMAGEBUTTON element
===================================================================

See :issue:`77345`

Description
===========

The :typoscript:`IMAGEBUTTON` element has been removed in TYPO3 v8.


Impact
======

Using the :typoscript:`IMAGEBUTTON` element is not working anymore, i.e. no :typoscript:`IMAGEBUTTON` element will be rendered.


Affected Installations
======================

All installations using the :typoscript:`IMAGEBUTTON` element.


Migration
=========

Remove all usages of the :typoscript:`IMAGEBUTTON` element.

.. index:: TypoScript, ext:form
