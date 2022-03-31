
.. include:: /Includes.rst.txt

=============================================================================
Breaking: #72412 - Removed deprecated code from language processing functions
=============================================================================

See :issue:`72412`

Description
===========

The following deprecated code has been removed:

* `LocalizationFactory::getParsedData` no support for moved language files
* class `LocallangArrayParser` has been removed completely

The following deprecated methods have been removed:

* `LanguageService::localizedFileRef()`


Impact
======

Using old locations of language file will result in no text being displayed.
Using the removed class will result in a fatal error.
Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use old locations of language files, instances which use the removed class LocallangArrayParser, instances
which use calls to the methods above.


Migration
=========

`LocalizationFactory::getParsedData` only supports the new location of language files
`LocallangArrayParser` use XLIFF language files now
`\TYPO3\CMS\Lang\LanguageService::localizedFileRef` no replacement; not needed when XLIFF files are used

.. index:: PHP-API
