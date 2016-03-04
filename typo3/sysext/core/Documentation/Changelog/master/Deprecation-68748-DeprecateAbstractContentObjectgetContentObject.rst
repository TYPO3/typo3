=========================================================================
Deprecation: #68748 - Deprecate AbstractContentObject::getContentObject()
=========================================================================

Description
===========

The method is renamed to getContentObjectRenderer(). The old method name is still present as a
deprecated alias, which will be removed in TYPO3 v10.


Impact
======

All method calls to getContentObject() must be changed to getContentObjectRenderer() until TYPO3 v9.
