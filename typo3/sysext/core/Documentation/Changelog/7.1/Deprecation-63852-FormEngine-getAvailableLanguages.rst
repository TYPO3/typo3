===================================================================
Deprecation: #63852 - Deprecate FormEngine::getAvailableLanguages()
===================================================================

Description
===========

``FormEngine::getAvailableLanguages()`` has been marked as deprecated.


Impact
======

Using ``getAvailableLanguages()`` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of ``FormEngine::getAvailableLanguages()``.


Migration
=========

No migration possible.
