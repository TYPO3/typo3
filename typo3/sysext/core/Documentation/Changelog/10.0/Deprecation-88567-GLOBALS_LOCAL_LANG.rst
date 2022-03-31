.. include:: /Includes.rst.txt

============================================
Deprecation: #88567 - $GLOBALS['LOCAL_LANG']
============================================

See :issue:`88567`

Description
===========

The global array :php:`$GLOBALS['LOCAL_LANG']` contains all labels from language
files that were loaded "globally". However, instead of having this in global
scope, it is more feasible to have it scoped to the actual :php:`LanguageService`
that loaded this data, in order to allow various language functionality in the
same PHP process without having to deal with global variables.

For this reason, it is discouraged to use :php:`$GLOBALS['LOCAL_LANG']`
but instead rely on :php:`LanguageService->includeLLfile()` which returns
the actual values as well, but only the ones loaded from this instance.

Since an instance of :php:`TYPO3\CMS\Core\Localization\LanguageService` is usually available via `$GLOBALS['LANG']` the
labels are accessible within PHP anyways.

Due to this change, the second and third arguments of :php:`LanguageService->includeLLFile()` have been marked as deprecated.


Impact
======

Calling the method above with an explicit second and/or third argument will
trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with third-party extensions using
:php:`$GLOBALS['LOCAL_LANG']` or the mentioned method with more than one argument,
which is very unlikely.


Migration
=========

Use the return value of :php:`LanguageService->includeLLFile()` and remove
the second and third arguments to work with label files.

.. index:: PHP-API, FullyScanned
