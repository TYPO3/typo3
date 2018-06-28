.. include:: ../../Includes.txt

==================================================
Deprecation: #85445 - TemplateService->getFileName
==================================================

See :issue:`85445`

Description
===========

The PHP method :php:`TYPO3\CMS\Core\TypoScript\TemplateService->getFileName()` has been marked as deprecated, as
it is technically extracted into separate functionality with modern architecture throwing PHP Exceptions when
a file name is invalid.

Along with the method comes the public property :php:`$fileCache` which acted as a simple first-level
in-memory cache, its access is deprecated, too.


Impact
======

Calling the method directly or accessing the public property will trigger a PHP deprecation message.


Affected Installations
======================

Any TYPO3 installation dealing with PHP code in Frontend (e.g. `$TSFE->tmpl->getFileName()`).


Migration
=========

Use :php:`TYPO3\CMS\Frontend\Resource\FilePathSanitizer->sanitize($filePath)` instead.

.. index:: Frontend, PHP-API, FullyScanned
