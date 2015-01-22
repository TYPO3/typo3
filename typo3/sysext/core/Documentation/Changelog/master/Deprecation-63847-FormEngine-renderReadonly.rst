===========================================================
Deprecation: #63847 - Deprecate FormEngine::$renderReadonly
===========================================================

Description
===========

The direct access to :code:`FormEngine::$renderReadonly` has been deprecated.


Impact
======

Using :code:`FormEngine::$renderReadonly` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of :code:`FormEngine::$renderReadonly`.


Migration
=========

Use :code:`AbstractFormElement::setRenderReadonly(TRUE)` to force all elements to be rendered as read only fields.

