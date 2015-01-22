==============================================
Deprecation: #63855 - Deprecate FormEngine::sL
==============================================

Description
===========

:code:`FormEngine::sL()` has been marked as deprecated.


Impact
======

Calling :code:`sL()` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, that use :code:`FormEngine::sL()`.


Migration
=========

Use :code:`getLanguageService()` instead.
