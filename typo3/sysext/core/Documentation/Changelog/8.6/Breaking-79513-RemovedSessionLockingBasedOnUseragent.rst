.. include:: /Includes.rst.txt

=============================================================
Breaking: #79513 - Removed session locking based on useragent
=============================================================

See :issue:`79513`

Description
===========

When using session data or user-login functionality with TYPO3, the default configuration was to
harden the session binding to the User Agent information sent by the HTTP request. If the user agent
information does not match, the session gets renewed and the user gets logged out.

The options :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['lockHashKeyWords']` and :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['lockHashKeyWords']`
were set to "useragent" by default to use this additional session locking check.

This case is especially problematic when having a larger website (e.g. a community platform) with
100K frontend users and the session lifetime set to 6 months. After every security update of the
browser or possibly a plugin, or if a version update is happening on Evergreen Browsers, then
all users would get logged out, which is inconvenient.

Based on the additional security level on top versus the user experience on the site, the "useragent"
functionality has been dropped. Since the "lockHashKeyWords" options did only work on "useragent"
and no other functionality was integrated, the option (and related, the database fields "ses_hashlock"
as well) has been removed without substitution.


Impact
======

The options :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['lockHashKeyWords']` and :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['lockHashKeyWords']`
are removed automatically when hitting the install tool.

The database fields 'fe_sessions.ses_hashlock' and 'be_sessions.ses_hashlock' have been removed.

The public property :php:`$lockHashKeyWords` of the PHP class `AbstractUserAuthentication` has been
removed and will throw a PHP Notice when trying to access it.

All other functionality related to sessions still works the same.


Affected Installations
======================

Any installation using the configuration options for custom checks based on the session handling
with third-party extensions, which is very unlikely.


Migration
=========

The TYPO3 Install Tool removes the configuration option for existing installations. Using the
"Database Comparison" view, it is possible to remove the fields from the database.

.. index:: LocalConfiguration, PHP-API
