.. include:: ../../Includes.txt

======================================================
Deprecation: #94958 - ContentObjectRenderer properties
======================================================

See :issue:`94958`

Description
===========

A couple of outdated and mostly unused properties of class
:php:`ContentObjectRenderer` have been deprecated:

* :php:`ContentObjectRenderer->align` - Unused
* :php:`ContentObjectRenderer->oldData` - Unused
* :php:`ContentObjectRenderer->alternativeData` - Never set, only output during debug
* :php:`ContentObjectRenderer->currentRecordTotal` - Set, but never used
* :php:`ContentObjectRenderer->recordRegister` - Unused


Impact
======

Those properties did not fit a purpose. Extensions shouldn't see
negative impact.


Affected Installations
======================

Instances with extensions that set or read these properties may be affected.
This is rather unlikely. The extension scanner finds canditates.


Migration
=========

Drop usages. The properties will vanish in v12.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
