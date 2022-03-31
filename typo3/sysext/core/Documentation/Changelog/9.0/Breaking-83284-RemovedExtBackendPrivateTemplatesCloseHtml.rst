.. include:: /Includes.rst.txt

=============================================================================
Breaking: #83284 - Removed EXT:backend/Resources/Private/Templates/Close.html
=============================================================================

See :issue:`83284`

Description
===========

The file :php:`EXT:backend/Resources/Private/Templates/Close.html` has been removed.


Impact
======

Accessing the file :php:`EXT:backend/Resources/Private/Templates/Close.html` will result in an empty
string returned or an exception, depending on the code to access it.


Affected Installations
======================

All instances, that manually access this file or use the extensions doing this..
The extension scanner of the install tool will find affected extensions.


Migration
=========

Use the file :php:`EXT:backend/Resources/Public/Html/Close.html` instead.

.. index:: Backend, NotScanned
