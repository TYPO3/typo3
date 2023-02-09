.. include:: /Includes.rst.txt

.. _deprecation-99916-1676027922:

=======================================================
Deprecation: #99916 - Site Language "direction" setting
=======================================================

See :issue:`99916`

Description
===========

A language configuration defined for a site has had various settings, one of
them being "direction". The setting is used to add the "dir" attribute to the
`<html>` tag of a frontend page in HTML format, defining the direction of the
language.

However, according to https://meta.wikimedia.org/wiki/Template:List_of_language_names_ordered_by_code
the list of languages that have a directionality of "right-to-left"
is fixed and does not need to be configured anymore.

Since TYPO3 v12 it is not needed to set this property in the site configuration
anymore, and is removed from the Backend UI. The information is now automatically
derived from the Locale setting of the Site Configuration.

As a result, the amount of options in the User Interface for integrators is
reduced.

The PHP method :php:`SiteLanguage->getDirection()` serves no purpose anymore and
is deprecated.


Impact
======

Using the PHP method will trigger a PHP deprecation notice.

An Administrator can not select a value for the "direction" setting anymore
via the TYPO3 Backend. However, when saving a site configuration via the
TYPO3 Backend will still keep the "direction" setting so no information is lost.


Affected installations
======================

TYPO3 installations actively accessing this property via PHP or TypoScript, and
mainly related for TYPO3 installations with languages that have a "right-to-left"
reading direction.


Migration
=========

No migration is needed as the explicit option is still evaluated. It is however
not needed in 99.99% of the use cases. If the locale of the site language in the
sites` :file:`config.yaml` is matching the natural direction of the language
(Arabic and direction = rtl), the setting `direction` can be removed.

Any calls to  :php:`SiteLanguage->getDirection()` can be replaced by
:php:`SiteLanguage->getLocale()->isRightToLeftLanguageDirection() ? 'rtl' : 'ltr`.

The Frontend Output does not set "ltr" in the `<html>` tag anymore, as this is the default
for HTML documents (see `https://www.w3.org/International/questions/qa-html-dir`).

.. index:: PHP-API, YAML, FullyScanned, ext:core
