.. include:: /Includes.rst.txt

===============================================================
Breaking: #84877 - Localization of page on column basis removed
===============================================================

See :issue:`84877`

Description
===========

The "Translate" buttons located in each column (colPos) in the page module have been replaced by one global action
button per language.


Impact
======

The possibility to translate each column of a page from a different source language has been removed. Instead, the whole
page gets translated with the locale action.


Affected Installations
======================

Every installation relying on having different sources per localization is affected.


Migration
=========

Manually migrate any content element by changing its parent by editing the element, in case mixed sources are used.

.. index:: Backend, NotScanned, ext:backend
