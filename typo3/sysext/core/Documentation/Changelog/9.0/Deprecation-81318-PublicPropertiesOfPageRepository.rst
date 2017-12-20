.. include:: ../../Includes.txt

=========================================================
Deprecation: #81318 - Public properties of PageRepository
=========================================================

See :issue:`81318`

Description
===========

The following properties within the PageRepository PHP class have been marked as deprecated, as they
were moved from public access to protected access:

* :php:`workspaceCache`
* :php:`error_getRootLine`
* :php:`error_getRootLine_failPid`

They should only be accessed from within the PHP class itself.


Impact
======

Accessing any of the properties directly within PHP will trigger a deprecation warning.


Affected Installations
======================

Extensions accessing one of the previously public properties directly.


Migration
=========

Remove the PHP calls and either extend the PHP class to your own needs or avoid accessing these properties.

.. index:: PHP-API, FullyScanned, Frontend