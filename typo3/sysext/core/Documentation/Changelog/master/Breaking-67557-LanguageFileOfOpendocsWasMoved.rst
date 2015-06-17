======================================================
Breaking: #67557 - Language file of Opendocs was moved
======================================================

Description
===========

The language file locallang_opendocs.xlf of EXT:opendocs has been moved to Resources/Private/locallang.xlf.


Impact
======

Inclusion of the file via ``$this->getLanguageService()->includeLLFile()`` or usage in ``<f:translate key="LLL:EXT:opendocs/locallang_opendocs.xlf:foobar" />.`` will fail.


Affected Installations
======================

Every extension relying on the existence of locallang_opendocs.xlf will be affected.


Migration
=========

Use Resources/Private/Language/locallang.xlf, if required.