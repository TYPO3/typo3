.. include:: /Includes.rst.txt

.. _feature-99048-1668081533:

========================================
Feature: #99048 - Site Settings Read API
========================================

See :issue:`99048`

Description
===========

Settings for site-specific functionality can now be retrieved by a dedicated
`SiteSettings` object, accessible via a Site object
like :php:`$site->getSettings()`.

Settings can be used in custom frontend code to deliver features which might
vary per-site for extensions.


Impact
======

Accessing site settings, which was previously possible via:

:php:`$site->getConfiguration()['settings']['redirects'] ?? []`

in custom PHP code, is now easier via the SiteSettings PHP object.

The Site Settings object can be used to access settings either by
the dot notation ("flat"), e.g.

:php:`$redirectStatusCodeForRedirects = (int)$siteSettings->get('redirects.httpStatusCode', 307);`

or by accessing all options for a certain group

:php:`$allSettingsRelatedToRedirects = $siteSettings->get('redirects');`

or even fetching all settings

:php:`$siteSettings->all();`

In addition, settings can now be accessed in TypoScript via :typoscript:`getData`
with the key `siteSettings`:

.. code-block:: typoscript

    page.10 = TEXT
    page.10.data = siteSettings:redirects.httpStatusCode

.. index:: PHP-API, TypoScript ext:core
