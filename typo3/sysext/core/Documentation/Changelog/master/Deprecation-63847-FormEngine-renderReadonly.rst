===========================================================
Deprecation: #63847 - Deprecate FormEngine::$renderReadonly
===========================================================

Description
===========

The direct access to :php:`FormEngine::$renderReadonly` has been deprecated.


Impact
======

Using :php:`FormEngine::$renderReadonly` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of :php:`FormEngine::$renderReadonly`.


Migration
=========

Use :php:`AbstractFormElement::setRenderReadonly(TRUE)` to force all elements to be rendered as read only fields.

