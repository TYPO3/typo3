.. include:: /Includes.rst.txt

.. _deprecation-99882-1675873624:

===========================================================
Deprecation: #99882 - Site language "typo3Language" setting
===========================================================

See :issue:`99882`

Description
===========

A language configuration defined for a site has had various settings, one of
them being :yaml:`typo3Language`. The setting is used to define the language key which
should be used for fetching the proper XLF file (such as :file:`de_AT.locallang.xlf`).

Since TYPO3 v12 it is unnecessary to set this property in the site configuration
and it is removed from the backend UI. The information is now automatically
derived from the :yaml:`locale` setting of the site configuration.

The previous value "default", which matched "en" as language key is now unnecessary
as "default" is now a synonym for "en".

As a result, the amount of options in the user interface for integrators is
reduced.


Impact
======

An administrator cannot select a value for the :yaml:`typo3Language` setting anymore
via the TYPO3 backend. If a custom value is required, the site configuration
needs to be manually edited and the :yaml:`typo3Language` setting needs to be added.

If this is the case, please file a bug report in order to give the TYPO3
development team feedback on what use case is required.

However, saving a site configuration via the TYPO3 backend will still
keep the :yaml:`typo3Language` setting so no values will be lost.


Affected installations
======================

TYPO3 installations created before TYPO3 v12.3.


Migration
=========

No migration is needed as the explicit option is still evaluated. It is however
recommended to check if the setting is really necessary.

Examples:

#.  If :yaml:`typo3Language: "default"` and :yaml:`locale: "en_US.UTF-8"`, the setting can be removed.
#.  If :yaml:`typo3Language: "pt_BR"` and :yaml:`locale: "pt_BR.UTF-8"`, the setting can be removed.
#.  If :yaml:`typo3Language: "de"` and :yaml:`locale: "de_AT.UTF-8"` , the setting can be removed,
    plus the label files check for :file:`de_AT.locallang.xlf` and :file:`de.locallang.xlf`
    as fallback when accessing a translated label.
#.  If :yaml:`typo3Language: "pt_BR"` and :yaml:`locale: "de_DE.UTF-8"` it is likely
    a misconfiguration in the setup, and should be analyzed if the custom value is really needed.

.. index:: YAML, NotScanned, ext:core
