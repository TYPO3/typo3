==============================================
Deprecation: #63855 - Deprecate FormEngine::sL
==============================================

Description
===========

:php:`FormEngine::sL()` has been marked as deprecated.


Impact
======

Calling :php:`sL()` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, that use :php:`FormEngine::sL()`.


Migration
=========

Use :php:`getLanguageService()` instead.
