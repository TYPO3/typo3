==============================================================
Deprecation: #63912 - Deprecate unused methods from FormEngine
==============================================================

Description
===========

``FormEngine::getSingleField_typeFlex_langMenu()`` has been marked as deprecated.
``FormEngine::getSingleField_typeFlex_sheetMenu()`` has been marked as deprecated.
``FormEngine::getSpecConfFromString()`` has been marked as deprecated.


Impact
======

Using ``getSingleField_typeFlex_langMenu()``, ``getSingleField_typeFlex_sheetMenu()`` and ``getSpecConfFromString()`` of FormEngine class will trigger a deprecation log message.


Affected installations
======================

Instances which use custom form elements, which make use of ``getSingleField_typeFlex_langMenu()``, ``getSingleField_typeFlex_sheetMenu()`` or ``getSpecConfFromString()``.


Migration
=========

For ``getSingleField_typeFlex_langMenu()`` and ``getSingleField_typeFlex_sheetMenu()`` no migration is possible, those methods were unused for a long time already and should not be needed at all.
For ``getSpecConfFromString()`` use method ``BackendUtility::getSpecConfParts()`` instead.
