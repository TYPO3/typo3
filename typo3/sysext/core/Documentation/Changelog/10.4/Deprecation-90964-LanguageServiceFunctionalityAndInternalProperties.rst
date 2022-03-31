.. include:: /Includes.rst.txt

===========================================================================
Deprecation: #90964 - LanguageService functionality and internal properties
===========================================================================

See :issue:`90964`

Description
===========

LanguageService - also known as :php:`$GLOBALS[LANG]` within TYPO3 Core
is used to fetch a label string from a XLF file and deliver the
translated value from that string.

Some functionality related to legacy functionality or internal logic has been marked as deprecated and changed visibility:

* :php:`LanguageService->LL_files_cache` - is now protected instead of public
* :php:`LanguageService->LL_labels_cache` - is now protected instead of public
* :php:`LanguageService->getLabelsWithPrefix()` - is deprecated as it is not needed
* :php:`LanguageService->getLLL()` - is now protected instead of public
* :php:`LanguageService->debugLL()` - is now protected instead of public

The method :php:`LanguageService->loadSingleTableDescription()` is marked as internal now.


Impact
======

Calling any of the methods or properties listed above will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with extensions of custom logic using the internals of specifics of the :php:`LanguageService` class.


Migration
=========

Use the Public API of the :php:`LanguageService` - namely :php:`sL()` and :php:`getLL()` directly.

.. index:: PHP-API, FullyScanned, ext:core
