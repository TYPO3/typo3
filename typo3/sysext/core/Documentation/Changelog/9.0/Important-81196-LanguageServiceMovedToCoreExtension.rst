.. include:: ../../Includes.txt

===========================================================
Important: #81196 - LanguageService moved to core extension
===========================================================

See :issue:`81196`

Description
===========

The PHP class `TYPO3\CMS\Lang\LanguageService` - very well known for being available as 
:php:`$GLOBALS['LANG']` in the TYPO3 backend scope, and responsible for translating labels from
XLF/XML files, has been moved to EXT:core, the core system extension.

The new class is named :php:`TYPO3\CMS\Core\Localization\LanguageService`. A class alias for backwards-
compatibility is available, so the instantiating or referencing the old class name still works.

The class alias functionality will be dropped in TYPO3 v10.

.. index:: PHP-API