.. include:: ../../Includes.txt

===========================================================================
Deprecation: #79327 - Deprecate AbstractUserAuthentication::veriCode method
===========================================================================

See :issue:`79327`

Description
===========

The :php:`AbstractUserAuthentication::veriCode` method has been marked as deprecated.

Right now all Backend urls require module token, so veriCode is not needed any more.
Veri token was used as an alternative verification when the JavaScript interface executes cmd's to tce_db.php from eg. MSIE 5.0 because the proper referer is not passed with this browser...


Impact
======

Calling :php:`AbstractUserAuthentication::veriCode` will log deprecation message.


Affected Installations
======================

Any installation having extensions calling :php:`AbstractUserAuthentication::veriCode`


Migration
=========

Remove calls to `veriCode` or any `vC` HTTP parameter evaluation from your code. Ensure your code uses `moduleToken` to protect backend urls.

.. index:: Backend, JavaScript, PHP-API