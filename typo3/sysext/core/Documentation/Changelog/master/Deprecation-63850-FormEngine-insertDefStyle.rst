==========================================================
Deprecation: #63850 - Deprecate FormEngine::insertDefStyle
==========================================================

Description
===========

FormEngine::insertDefStyle is deprecated.


Impact
======

Using ``insertDefStyle`` of FormEngine class will trigger a deprecation log message.

Affected installations
======================

Instances which use custom form elements, which make use of ``FormEngine::insertDefStyle``.

Migration
=========

All form fields should extend the ``AbstractFormElement`` class and make use of the new property ``AbstractFormElement::$cssClassTypeElementPrefix``
