.. include:: ../../Includes.txt

==================================================
Deprecation: #93038 - ReferenceIndex runtime cache
==================================================

See :issue:`93038`

Description
===========

Two methods of class :php:`ReferenceIndex` have been deprecated:

* :php:`ReferenceIndex->enableRuntimeCache()`
* :php:`ReferenceIndex->disableRuntimeCache()`


Impact
======

Calling these methods raises a deprecation level log entry.


Affected Installations
======================

Instances with extensions calling above methods are affected. The extension
scanner locates candidates.


Migration
=========

The method calls can be dropped, cache handling is done internally.

.. index:: PHP-API, FullyScanned, ext:core
