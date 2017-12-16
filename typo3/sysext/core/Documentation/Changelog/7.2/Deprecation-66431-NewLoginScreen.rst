
.. include:: ../../Includes.txt

======================================
Deprecation: #66431 - New Login Screen
======================================

See :issue:`66431`

Description
===========

The login screen is now supporting background images and adjustable highlight
colors out of the box. Settings for the login screen can now be accessed in
the backend extension settings.

Since the needed settings for the login screen were moved to the backend extension
configuration, `$GLOBALS['TBE_STYLES']['logo_login']` is only used as fallback.
The option has been marked as deprecated and will be removed with TYPO3 CMS 8.


Impact
======

`$GLOBALS['TBE_STYLES']['logo_login']` will add a deprecation log message and
is still used as fallback to the new option but will be removed with TYPO3 CMS 8.


Affected Installations
======================

Installations that use `$GLOBALS['TBE_STYLES']['logo_login']` to set an alternative
logo for the backend login.


Migration
=========

Remove the `$GLOBALS['TBE_STYLES']['logo_login']` from your setup and go to the
extension manager to edit the configuration for the backend extension.
