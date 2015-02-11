============================================================
Deprecation: #63864 - Deprecate FormEngine::renderVDEFDiff()
============================================================

Description
===========

``FormEngine::renderVDEFDiff()`` has been marked as deprecated.


Impact
======

Using ``FormEngine::renderVDEFDiff()`` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of ``FormEngine::renderVDEFDiff()``.


Migration
=========

No migration possible.
