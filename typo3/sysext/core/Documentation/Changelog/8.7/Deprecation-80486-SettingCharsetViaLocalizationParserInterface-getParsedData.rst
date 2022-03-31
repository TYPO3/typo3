.. include:: /Includes.rst.txt

======================================================================================
Deprecation: #80486 - Setting charset via LocalizationParserInterface->getParsedData()
======================================================================================

See :issue:`80486`

Description
===========

The :php:`LocalizationParserInterface->getParsedData()` contains a third parameter to hand over a value
for the charset used.

This third parameter has been marked as deprecated, as it is not in use anymore.


Affected Installations
======================

Any installation with an extension that extends the LocalizationParser functionality with a custom
PHP class implementing the :php:`LocalizationParserInterface`.


Migration
=========

If implementing the :php:`LocalizationParserInterface`, be aware that this third parameter will be dropped in TYPO3 v9.

.. index:: PHP-API
