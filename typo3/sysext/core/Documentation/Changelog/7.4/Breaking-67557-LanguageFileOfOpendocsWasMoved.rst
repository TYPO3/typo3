
.. include:: ../../Includes.txt

======================================================
Breaking: #67557 - Language file of Opendocs was moved
======================================================

See :issue:`67557`

Description
===========

The language file :file:`locallang_opendocs.xlf` of EXT:opendocs has been moved to
:file:`Resources/Private/locallang.xlf`.


Impact
======

Inclusion of the file via `$this->getLanguageService()->includeLLFile()` or usage in
`<f:translate key="LLL:EXT:opendocs/locallang_opendocs.xlf:foobar" />.` will fail.


Affected Installations
======================

Every extension relying on the existence of :file:`locallang_opendocs.xlf` will be affected.


Migration
=========

Use :file:`Resources/Private/Language/locallang.xlf`, if required.


.. index:: PHP-API, Backend, ext:opendocs
