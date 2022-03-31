.. include:: /Includes.rst.txt

=============================================================
Deprecation: #84549 - Deprecate methods in CoreVersionService
=============================================================

See :issue:`84549`

Description
===========

The core version service has been refactored to make use of the new REST API available via
`https://get.typo3.org/v1/api/doc <https://get.typo3.org/v1/api/doc>`_.

Due to that refactoring multiple methods in class :php:`CoreVersionService` have been marked as deprecated:

* :php:`getDownloadBaseUrl()`
* :php:`isYoungerPatchDevelopmentReleaseAvailable()`
* :php:`getYoungestPatchDevelopmentRelease()`
* :php:`updateVersionMatrix()`


Impact
======

Usage of any of these methods will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any that use the mentioned methods.


Migration
=========

* For :php:`getDownloadBaseUrl()` use `https://get.typo3.org` directly
* For :php:`isYoungerPatchDevelopmentReleaseAvailable()` use :php:`isYoungerPatchReleaseAvailable()`
  as the current releases do not make use of development suffixes (like alpha or rc) anymore
* For :php:`getYoungestPatchDevelopmentRelease()` use :php:`getYoungestPatchRelease()`
* :php:`updateVersionMatrix()` needs no replacement method - instead the necessary information can be
  fetched directly via the REST API

.. index:: Backend, PHP-API, PartiallyScanned, ext:install
