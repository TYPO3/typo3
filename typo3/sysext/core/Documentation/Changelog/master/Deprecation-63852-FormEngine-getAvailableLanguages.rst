===================================================================
Deprecation: #63852 - Deprecate FormEngine::getAvailableLanguages()
===================================================================

Description
===========

:code:`FormEngine::getAvailableLanguages()` has been deprecated.


Impact
======

Using :code:`getAvailableLanguages()` of FormEngine class will trigger a deprecation log message.

Affected installations
======================

Instances which use custom form elements, which make use of :code:`FormEngine::getAvailableLanguages()`.

Migration
=========

No migration possible.
