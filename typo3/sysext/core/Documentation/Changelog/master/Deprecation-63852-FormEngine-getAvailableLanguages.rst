===================================================================
Deprecation: #63852 - Deprecate FormEngine::getAvailableLanguages()
===================================================================

Description
===========

:php:`FormEngine::getAvailableLanguages()` has been deprecated.


Impact
======

Using :php:`getAvailableLanguages()` of FormEngine class will trigger a deprecation log message.

Affected installations
======================

Instances which use custom form elements, which make use of :php:`FormEngine::getAvailableLanguages()`.

Migration
=========

No migration possible.
