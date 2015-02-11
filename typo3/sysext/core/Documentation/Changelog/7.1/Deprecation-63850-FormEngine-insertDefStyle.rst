==========================================================
Deprecation: #63850 - Deprecate FormEngine::insertDefStyle
==========================================================

Description
===========

``FormEngine::insertDefStyle`` has been marked as deprecated.


Impact
======

Using ``insertDefStyle`` of FormEngine class will trigger a deprecation log message.

Affected installations
======================

Instances which use custom form elements, which make use of ``FormEngine::insertDefStyle``.

Migration
=========

The property is unused and can be removed.
