
.. include:: ../../Includes.txt

===========================================================
Breaking: #72385 - Removed deprecated code from DataHandler
===========================================================

See :issue:`72385`

Description
===========

Removed deprecated code from DataHandler

The following properties have been removed:

`stripslashes_values`
`clear_flexFormData_vDEFbase`
`include_filefunctions`

The following methods have been removed:

`checkValue_text()`
`checkValue_input()`
`checkValue_check()`
`checkValue_radio()`
`checkValue_group_select()`
`checkValue_flex()`

Additionally, the method `getLocalTCE()` does not accept any parameter anymore.

Impact
======

Using one of these methods or properties will throw a fatal error.


Affected Installations
======================

An extension relying on one of these public properties or methods will fail.


Migration
=========

These functions are internal and should not be used outside of the core.

.. index:: PHP-API, Backend
