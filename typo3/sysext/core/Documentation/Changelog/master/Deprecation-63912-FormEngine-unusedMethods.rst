==============================================================
Deprecation: #63912 - Deprecate unused methods from FormEngine
==============================================================

Description
===========

:code:`FormEngine::getSingleField_typeFlex_langMenu()` has been deprecated.
:code:`FormEngine::getSingleField_typeFlex_sheetMenu()` has been deprecated.
:code:`FormEngine::getSpecConfFromString()` has been deprecated.


Impact
======

Using :code:`getSingleField_typeFlex_langMenu()`, :code:`getSingleField_typeFlex_sheetMenu()` and :code:`getSpecConfFromString()` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of :code:`getSingleField_typeFlex_langMenu()`, :code:`getSingleField_typeFlex_sheetMenu()` or :code:`getSpecConfFromString()`.


Migration
=========

For :code:`getSingleField_typeFlex_langMenu()` and :code:`getSingleField_typeFlex_sheetMenu()` no migration is possible, those methods were unused for a long time already and should not be needed at all.
For :code:`getSpecConfFromString()` use method :code:`BackendUtility::getSpecConfParts()` instead.
