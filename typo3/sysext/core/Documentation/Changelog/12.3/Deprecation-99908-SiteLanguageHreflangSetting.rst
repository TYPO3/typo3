.. include:: /Includes.rst.txt

.. _deprecation-99908-1675976983:

======================================================
Deprecation: #99908 - Site language "hreflang" setting
======================================================

See :issue:`99908`

Description
===========

A language configuration defined for a site has had various settings, one of
them being :yaml:`hreflang`. The setting is used to generate hreflang meta tags to
link to alternative language versions of a translated page, and to add the
:html:`lang` attribute to the :html:`<html>` tag of a frontend page in HTML format.

Since TYPO3 v12 it is not necessary to set this property in the site configuration
anymore. The information is now automatically
derived from the :yaml:`locale` setting of the site configuration if not set
in the site configuration.

This also affects the TypoScript :typoscript:`getData` property
:typoscript:`siteLanguage:hrefLang`, and the TypoScript condition
:typoscript:`[siteLanguage("hrefLang")]`.


Impact
======

Using the TypoScript settings or the PHP method will trigger a PHP deprecation
notice.

An administrator cannot select a value for the :yaml:`hreflang` setting anymore
via the TYPO3 backend. However, when saving a site configuration via the
TYPO3 backend it will still keep the :yaml:`hreflang` setting so no information is lost.


Affected installations
======================

TYPO3 installations actively accessing this property via PHP or TypoScript.


Migration
=========

No migration is needed as the explicit option is still evaluated. It is however
recommended to check if the setting is really necessary, and if the locale of
the site language in the :file:`config.yaml` matches the same value - even in a
different format (:yaml:`locale: "de_AT.UTF-8"`, :yaml:`hreflang: "de-AT"`) - the setting
:yaml:`hreflang` can be removed.

Any calls to :php:`SiteLanguage->getHrefLang()` can be replaced by
:php:`SiteLanguage->getLocale()->getName()`.

As for TypoScript, it is recommended to use :typoscript:`siteLanguage:locale:full`
instead of :typoscript:`siteLanguage:hrefLang`.

.. index:: Frontend, PHP-API, TypoScript, YAML, PartiallyScanned, ext:core
