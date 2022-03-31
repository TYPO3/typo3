.. include:: /Includes.rst.txt

=============================================================
Deprecation: #86002 - TSFE constructor with no_cache argument
=============================================================

See :issue:`86002`

Description
===========

The 4th constructor argument of the PHP class :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController`
(a.k.a. "TSFE") was previously used to determine if the GET/POST parameter "no_cache" was set, which is
moved to a PSR-15 middleware now, making the argument obsolete. This argument is now set to "null" by default.


Impact
======

If anything other than the null value is given to the constructor method, a PHP :php:`E_USER_DEPRECATED` error is triggered.


Affected Installations
======================

TYPO3 installations with extensions that instantiate the PHP class manually and setting the 4th
constructor argument.


Migration
=========

Set the constructor argument to "null" when instantiating the class manually, use `$tsfe->set_no_cache()` instead
to manually disable the caching mechanism.

.. index:: Frontend, PHP-API, FullyScanned
