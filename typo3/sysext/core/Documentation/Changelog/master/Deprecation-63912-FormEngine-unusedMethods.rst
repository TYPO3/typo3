==============================================================
Deprecation: #63912 - Deprecate unused methods from FormEngine
==============================================================

Description
===========

:php:`FormEngine::getSingleField_typeFlex_langMenu()` has been deprecated.
:php:`FormEngine::getSingleField_typeFlex_sheetMenu()` has been deprecated.
:php:`FormEngine::getSpecConfFromString()` has been deprecated.


Impact
======

Using :php:`getSingleField_typeFlex_langMenu()`, :php:`getSingleField_typeFlex_sheetMenu()` and :php:`getSpecConfFromString()` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of :php:`getSingleField_typeFlex_langMenu()`, :php:`getSingleField_typeFlex_sheetMenu()` or :php:`getSpecConfFromString()`.


Migration
=========

For :php:`getSingleField_typeFlex_langMenu()` and :php:`getSingleField_typeFlex_sheetMenu()` no migration is possible, those methods were unused for a long time already and should not be needed at all.
For :php:`getSpecConfFromString()` use method :php:`BackendUtility::getSpecConfParts()` instead.
