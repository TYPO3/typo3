================================================
Deprecation: #63855 - Deprecate FormEngine::sL()
================================================

Description
===========

``FormEngine::sL()`` has been marked as deprecated.


Impact
======

Calling ``sL()`` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, that use ``FormEngine::sL()``.


Migration
=========

Use ``getLanguageService()`` instead.
