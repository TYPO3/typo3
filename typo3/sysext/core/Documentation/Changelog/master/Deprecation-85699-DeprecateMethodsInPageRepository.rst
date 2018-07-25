.. include:: ../../Includes.txt

=========================================================
Deprecation: #85699 - Deprecate methods in PageRepository
=========================================================

See :issue:`85699`

Description
===========

The methods :php:`getMovePlaceholder` and :php:`movePlhOL()` in the class :php:`PageRepository`
have been marked as internal.

The methods :php:`getRecordsByField` and :php:`getFileReferences()` in the class :php:`PageRepository`
PageRepository have been marked as deprecated and will be removed in TYPO3 v10. Both methods are not in use anymore by the TYPO3 core.


Impact
======

Calling one of the mentioned methods will trigger a deprecation warning.


Affected Installations
======================

Third party code which accesses the methods.


Migration
=========

No direct migration available.
If you need one of the mentioned methods you can copy them over to your extension.

.. index:: Frontend, FullyScanned
