.. include:: /Includes.rst.txt

.. _deprecation-99908-1675976983:

======================================================
Deprecation: #99908 - Site Language "hreflang" setting
======================================================

See :issue:`99908`

Description
===========

A language configuration defined for a site has had various settings, one of
them being "hreflang". The setting is used to generate hreflang meta tags to
link to alternative language versions of a translated page, and to add the
"lang" attribute to the `<html>` tag of a frontend page in HTML format.

Since TYPO3 v12 it is not needed to set this property in the site configuration
anymore, and is removed from the Backend UI. The information is now automatically
derived from the Locale setting of the Site Configuration.

As a result, the amount of options in the User Interface for integrators is
reduced.

The PHP method :php:`SiteLanguage->getHrefLang()` serves no purpose
anymore and is deprecated.

This also affects the TypoScript "getData" property
:typoscript:`siteLanguage:hrefLang`, and the TypoScript condition
:typoscript:`[siteLanguage("hrefLang")]`.


Impact
======

Using the TypoScript settings or the PHP method will trigger a PHP deprecation
notice.

An Administrator can not select a value for the "hreflang" setting anymore
via the TYPO3 Backend. However, when saving a site configuration via the
TYPO3 Backend will still keep the "hreflang" setting so no information is lost.


Affected installations
======================

TYPO3 installation actively accessing this property via PHP or TypoScript.


Migration
=========

No migration is needed as the explicit option is still evaluated. It is however
recommended to evaluate if the setting is really needed, and if the locale of
the site language in the :file:`config.yaml` matches the same value - even in a
different format (locale: "de_AT.UTF-8", hreflang: "de-AT") - the setting
`hreflang` can be removed.

Any calls to  :php:`SiteLanguage->getHrefLang()` can be replaced by
:php:`SiteLanguage->getLocale()->getName()`.

As for TypoScript, it is recommended to use :typoscript:`siteLanguage:locale:full`
instead of :typoscript:`siteLanguage:hrefLang`.

.. index:: Frontend, PHP-API, TypoScript, YAML, PartiallyScanned, ext:core
