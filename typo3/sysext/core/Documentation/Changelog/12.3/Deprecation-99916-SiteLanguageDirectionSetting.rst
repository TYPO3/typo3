.. include:: /Includes.rst.txt

.. _deprecation-99916-1676027922:

=======================================================
Deprecation: #99916 - Site language "direction" setting
=======================================================

See :issue:`99916`

Description
===========

A language configuration defined for a site has had various settings, one of
them being :yaml:`direction`. The setting is used to add the :html:`dir` attribute to the
:html:`<html>` tag of a frontend page in HTML format, defining the direction of the
language.

However, according to https://meta.wikimedia.org/wiki/Template:List_of_language_names_ordered_by_code
the list of languages that have a directionality of "right-to-left"
is fixed and does not need to be configured anymore.

Since TYPO3 v12 it is not necessary to set this property in the site configuration
anymore, and has been removed from the backend UI. The information is now automatically
derived from the :yaml:`locale` setting of the site configuration.

As a result, the amount of options in the user interface for integrators is
reduced.

The PHP method :php:`SiteLanguage->getDirection()` serves no purpose anymore and
is deprecated.


Impact
======

Using the PHP method will trigger a PHP deprecation notice.

An administrator can not select a value for the :yaml:`direction` setting anymore
via the TYPO3 backend. However, when saving a site configuration via the
TYPO3 backend it will still keep the :yaml:`direction` setting so no information is lost.


Affected installations
======================

TYPO3 installations actively accessing this property via PHP or TypoScript, and
mainly related to TYPO3 installations with languages that have a "right-to-left"
reading direction.


Migration
=========

No migration is needed as the explicit option is still evaluated. It is however
not necessary in 99.99% of the use cases. If the locale of the site language in the
sites` :file:`config.yaml` matches the natural direction of the language
(Arabic and direction = rtl), the setting :yaml:`direction` can be removed.

Any calls to :php:`SiteLanguage->getDirection()` can be replaced by
:php:`SiteLanguage->getLocale()->isRightToLeftLanguageDirection() ? 'rtl' : 'ltr`.

The frontend output does not set :html:`ltr` in the :html:`<html>` tag anymore, as this is the default
for HTML documents (see https://www.w3.org/International/questions/qa-html-dir).

.. index:: PHP-API, YAML, FullyScanned, ext:core
