
.. include:: /Includes.rst.txt

===========================================================
Breaking: #56951 - Remove unused methods in PagePositionMap
===========================================================

See :issue:`56951`

Description
===========

Remove unused methods in PagePositionMap


Impact
======

A fatal error will be thrown if one of the removed methods is used.
The removed methods are:

`insertQuadLines`
`JSimgFunc`


Affected Installations
======================

Installations that use one of the removed methods.


Migration
=========

Use proper styling for a tree list.


.. index:: PHP-API, Backend
