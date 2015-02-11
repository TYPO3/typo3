=================================================
Deprecation: #63878 - Deprecate FormEngine::getLL
=================================================

Description
===========

``FormEngine::getLL()`` has been marked as deprecated.


Impact
======

Using ``getLL()`` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of ``FormEngine::getLL()``.


Migration
=========

Use methods like ``sL`` of ``languageService`` directly.