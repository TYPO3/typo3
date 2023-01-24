.. include:: /Includes.rst.txt

.. _deprecation-99905-1675963182:

=======================================================
Deprecation: #99905 - Site Language "iso-639-1" setting
=======================================================

See :issue:`99905`

Description
===========

A language configuration defined for a site has had various settings, one of
them being "iso-639-1" (also known as "twoLetterIsoCode").

This setting was previously introduced to define the actual ISO 639-1 code, which
was different from the Locale or the "typo3Language" setting. However,
this information can now properly retrieved from the method:
:php:`SiteLanguage->getLocale()->getLanguageCode()`.

Since TYPO3 v12 it is not needed to set this property in the site configuration
anymore, and it is removed from the Backend UI. The information is now automatically
derived from the Locale setting of the Site Configuration.

This property originally came from an option in TypoScript called
:typoscript:`config.sys_language_isocode` which in turn was created in favor of
the previous `sys_language` database table. TYPO3 Core never evaluated this
setting properly before TYPO3 v9.

As a result, the amount of options in the User Interface for integrators is
reduced.

The PHP method :php:`SiteLanguage->getTwoLetterIsoCode()` serves now purpose
anymore and is deprecated.

This also affects the TypoScript "getData" property :typoscript:`siteLanguage:twoLetterIsoCode`,
and the TypoScript condition :typoscript:`[siteLanguage("twoLetterIsoCode")]`.


Impact
======

Using the TypoScript settings or the PHP method will trigger a PHP deprecation notice.

An Administrator can not select a value for the "iso-639-1" setting anymore
via the TYPO3 Backend. However, when saving a site configuration via the
TYPO3 Backend will still keep the "iso-639-1" setting so no information is lost.


Affected installations
======================

TYPO3 installation actively accessing this property via PHP or TypoScript.


Migration
=========

No migration is needed as the explicit option is still evaluated. It is however
recommended to evaluate if the setting is really needed, and if the first part of the
`locale` setting matches the `iso-639-1` setting. If so, the line with `iso-639-1` can be removed.

As for TypoScript, it is recommended to use :typoscript:`siteLanguage:locale:languageCode`
instead of :typoscript:`siteLanguage:twoLetterIsoCode`.

.. index:: PHP-API, TypoScript, YAML, PartiallyScanned, ext:frontend
