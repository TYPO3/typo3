.. include:: /Includes.rst.txt

.. _deprecation-99905-1675963182:

=======================================================
Deprecation: #99905 - Site language "iso-639-1" setting
=======================================================

See :issue:`99905`

Description
===========

A language configuration defined for a site has had various settings, one of
them being :yaml:`iso-639-1` (also known as "twoLetterIsoCode").

This setting was previously introduced to define the current ISO 639-1 code, which
was different from the :yaml:`locale` or the :yaml:`typo3Language` setting. However,
this information is now properly retrieved with the method:
:php:`SiteLanguage->getLocale()->getLanguageCode()`.

Since TYPO3 v12 it is not necessary to set this property in the site configuration
anymore, and it has been removed from the backend UI. The information is now automatically
derived from the :yaml:`locale` setting of the site configuration.

This property originally came from an option in TypoScript called
:typoscript:`config.sys_language_isocode` which in turn was created in favor of
the previous :sql:`sys_language` database table. The TYPO3 Core never evaluated this
setting properly before TYPO3 v9.

As a result, the amount of options in the user interface for integrators is
reduced.

The PHP method :php:`SiteLanguage->getTwoLetterIsoCode()` serves no purpose
anymore and is deprecated.

This also affects the TypoScript :typoscript:`getData` property :typoscript:`siteLanguage:twoLetterIsoCode`,
and the TypoScript condition :typoscript:`[siteLanguage("twoLetterIsoCode")]`.


Impact
======

Using the TypoScript settings or the PHP method will trigger a PHP deprecation notice.

An administrator cannot select a value for the :yaml:`iso-639-1` setting anymore
via the TYPO3 backend. However, saving a site configuration via the
TYPO3 backend will still keep the :yaml:`iso-639-1` setting so no information is lost.


Affected installations
======================

TYPO3 installations actively accessing this property via PHP or TypoScript.


Migration
=========

No migration is needed as the explicit option is still evaluated. It is however
recommended to check if the setting is really necessary, and if the first part of the
:yaml:`locale` setting matches the :yaml:`iso-639-1` setting. If so, the line with
:yaml:`iso-639-1` can be removed.

As for TypoScript, it is recommended to use :typoscript:`siteLanguage:locale:languageCode`
instead of :typoscript:`siteLanguage:twoLetterIsoCode`.

.. index:: PHP-API, TypoScript, YAML, PartiallyScanned, ext:frontend
