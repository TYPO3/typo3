.. include:: ../../Includes.txt

==========================================================================
Deprecation: #85005 - Deprecate methods and constants in ValidatorResolver
==========================================================================

See :issue:`85005`

Description
===========

The following methods within :php:`TYPO3\CMS\Extbase\Validation\ValidatorResolver` have been marked
as deprecated:

- :php:`buildSubObjectValidator`
- :php:`parseValidatorAnnotation`
- :php:`parseValidatorOptions`
- :php:`unquoteString`

The following constants within :php:`TYPO3\CMS\Extbase\Validation\ValidatorResolver` have been marked
as deprecated:

- :php:`PATTERN_MATCH_VALIDATORS`
- :php:`PATTERN_MATCH_VALIDATOROPTIONS`

Impact
======

Calling any of the deprecated methods above will trigger a PHP :php:`E_USER_DEPRECATED` error.
Using any of the deprecated constants above will not ttrigger a PHP :php:`E_USER_DEPRECATED` error but will stop working in TYPO3 v10.0.


Affected Installations
======================

Any TYPO3 installation with a custom extension making use of these methods and constants. As these constants and methods
are to be considered internal api it's very unlikely that anyone is affected by this change at all.

Migration
=========

There is none.

.. index:: PHP-API, ext:extbase, PartiallyScanned
