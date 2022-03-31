
.. include:: /Includes.rst.txt

===================================================================
Breaking: #72826 - Removed custom charset configuration for locales
===================================================================

See :issue:`72826`

Description
===========

The TYPO3 Frontend resolved the TypoScript option `config.locale_all` and stored the charset part within
`$TSFE->localeCharset`. If the option `locale_all` did not provide a charset (e.g. when it is set to `de_AT`
instead of `de_AT.UTF-8` a "best guess" was done based on a static list set up in 2004.

The option `$TSFE->localeCharset` has been removed, along with the following calculation options and methods
available in the CharsetConverter class:

* CharsetConverter->lang_to_script
* CharsetConverter->script_to_charset_unix
* CharsetConverter->script_to_charset_windows
* CharsetConverter->locale_to_charset
* CharsetConverter->get_locale_charset()

The localeCharset option was solely used within the TypoScript functionality `stdWrap.strftime` when no
custom character set was given, and a character set conversion from the "localeCharset" (based on the best guess
or explicitly set via `config.locale_all = de_AT.UTF-8` and it was different than the renderCharset option of
the TYPO3 Frontend.


Impact
======

When custom locales are configured in TypoScript which are not present on the server, or the character set of
`config.locale_all` differs from the `config.renderCharset`, or `config.locale_all` does not set a character set,
could lead to unexpected output in the TYPO3 Frontend.


Affected Installations
======================

Instances which have a different `config.locale_all` character set given than set via `config.renderCharset`, or on
servers that don't have the charset of the locale available but the output should be a certain but not given character set.


Migration
=========

As this is a misconfiguration and only necessary if e.g. can not handle UTF-8 locales, `config.set_locale` can explicitly
be set to `de_AT@iso-8859-15` and the output should be renderCharset. On instances where `stdWrap.strftime`is used,
the subproperty `charset` can be set to the custom character set (e.g. `iso-8859-15`).

In each case, it should be configured that the `config.locale_all` option should have a character set given, to avoid
any side-effects with the TypoScript stdWrap option `strftime`.

.. index:: PHP-API, TypoScript, Frontend
