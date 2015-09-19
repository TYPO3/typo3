================================================
Deprecation: #69078 - TemplateService::$tempPath
================================================

Description
===========

The ``\TYPO3\CMS\Core\TypoScript\TemplateService::$tempPath`` member variable is not used anymore inside the core,
therefore it has been marked as deprecated and will be removed with CMS 8.


Affected Installations
======================

Any installation using third party code, which accesses ``TemplateService::$tempPath``.


Migration
=========

Remove any reference to ``TemplateService::$tempPath``.
