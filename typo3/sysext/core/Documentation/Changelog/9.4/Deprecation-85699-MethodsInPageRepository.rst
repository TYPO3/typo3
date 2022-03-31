.. include:: /Includes.rst.txt

=======================================================
Deprecation: #85699 - Various methods in PageRepository
=======================================================

See :issue:`85699`

Description
===========

The methods :php:`PageRepository::getMovePlaceholder()` and :php:`PageRepository::movePlhOL()`
have been marked as internal.

The methods :php:`PageRepository::getRecordsByField` and :php:`PageRepository::getFileReferences()`
have been marked as deprecated and will be removed in TYPO3 v10.


Impact
======

Calling one of the mentioned methods will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Third party code which calls the methods mentioned above.


Migration
=========

No direct migration available.
If you need one of the mentioned methods you can copy them over to your extension.

.. index:: Frontend, FullyScanned
