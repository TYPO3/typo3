
.. include:: ../../Includes.txt

==================================================
Deprecation: #77477 - TemplateService->fileContent
==================================================

See :issue:`77477`

Description
===========

The method `fileContent` within the class `TemplateService` has been marked as deprecated.


Impact
======

Calling the method will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance with a third-party extension calling the method directly.


Migration
=========

Implement the same logic directly in PHP with `getFileName()` and `file_get_contents()`.

.. index:: PHP-API