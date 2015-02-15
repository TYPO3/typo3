=======================================================
Deprecation: #63889 - Deprecate FormEngine::getTSCpid()
=======================================================

Description
===========

:code:`FormEngine::getTSCpid()` has been marked as deprecated.


Impact
======

Using :code:`FormEngine::getTSCpid()` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of :code:`FormEngine::getTSCpid()`.


Migration
=========

Use :code:`BackendUtility::getTSCpidCached()` instead.
