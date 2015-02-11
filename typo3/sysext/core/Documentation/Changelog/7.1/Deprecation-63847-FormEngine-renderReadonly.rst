===========================================================
Deprecation: #63847 - Deprecate FormEngine::$renderReadonly
===========================================================

Description
===========

The direct access to ``FormEngine::$renderReadonly`` has been marked as deprecated.


Impact
======

Using ``FormEngine::$renderReadonly`` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of ``FormEngine::$renderReadonly``.


Migration
=========

Use ``AbstractFormElement::setRenderReadonly(TRUE)`` to force all elements to be rendered as read only fields.

