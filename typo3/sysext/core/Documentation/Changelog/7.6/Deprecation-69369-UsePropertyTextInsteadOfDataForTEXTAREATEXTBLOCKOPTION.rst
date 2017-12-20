
.. include:: ../../Includes.txt

===================================================================
Deprecation: #69369 - Use property text instead of data in ext:form
===================================================================

See :issue:`69369`

Description
===========

The FORM elements `TEXTAREA` and `OPTION` currently use "data" as property
name to define default "values" which are used as human readable
"labels" inside the specific tag. Furthermore, the `TEXTBLOCK` element uses
the "content" property to define custom text. All other `FORM` elements
use "value". Since "data" implies the possibility to use computed
values, this patch deprecates "data" and adds a new property
called "text".


Impact
======

Using the property "data" will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation using `TEXTAREA`, `OPTION` and `TEXTBLOCK` elements
which use the property "data".


Migration
=========

Remove usage of the "data" property and use "text" instead. Opening a
specific form with the form wizard and storing the form again will also
migrate from "data" to "text".


.. index:: ext:form
