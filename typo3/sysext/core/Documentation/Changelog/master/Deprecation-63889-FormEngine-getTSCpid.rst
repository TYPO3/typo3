=====================================================
Deprecation: #63889 - Deprecate FormEngine::getTSCpid
=====================================================

Description
===========

:php:`FormEngine::getTSCpid()` has been deprecated.


Impact
======

Using :php:`FormEngine::getTSCpid()` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of :php:`FormEngine::getTSCpid()`.


Migration
=========

Use :php:`BackendUtility::getTSCpidCached()` instead.
