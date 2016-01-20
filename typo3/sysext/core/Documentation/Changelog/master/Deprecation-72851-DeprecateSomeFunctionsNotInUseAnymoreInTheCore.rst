=============================================================================
Deprecation: #72851 - Deprecate some functions not in use anymore in the core
=============================================================================

Description
===========

Deprecate some unused methods:

``BackendUtility::processParams()``
``BackendUtility::makeConfigForm()``
``BackendUtility::titleAltAttrib()``
``BackendUtility::getSQLselectableList()``
``ContentObjectRenderer->processParams()``


Impact
======

Calling one of the aforementioned methods will write an entry in the deprecation log.


Affected Installations
======================

Instances with custom backend modules that use one of the aforementioned methods.