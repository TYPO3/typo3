
.. include:: ../../Includes.txt

===============================================
Deprecation: #76101 - remove SoloFieldContainer
===============================================

See :issue:`76101`

Description
===========

The `render()` method of the `SoloFieldContainer` class has been marked as deprecated.
It is not used in core anymore.


Impact
======

Using the method will trigger a deprecation log entry.


Affected Installations
======================

Instances with custom extensions that use `render()` from `SoloFieldContainer`.


Migration
=========

Use the render method from the ListOfFieldsContainer class.

.. index:: PHP-API, Backend
