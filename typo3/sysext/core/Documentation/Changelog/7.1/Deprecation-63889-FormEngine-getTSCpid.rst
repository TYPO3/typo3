=======================================================
Deprecation: #63889 - Deprecate FormEngine::getTSCpid()
=======================================================

Description
===========

``FormEngine::getTSCpid()` has been marked as deprecated.


Impact
======

Using ``FormEngine::getTSCpid()`` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of ``FormEngine::getTSCpid()``.


Migration
=========

Use ``BackendUtility::getTSCpidCached()`` instead.
