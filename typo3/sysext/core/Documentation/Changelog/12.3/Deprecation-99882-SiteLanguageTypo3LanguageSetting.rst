.. include:: /Includes.rst.txt

.. _deprecation-99882-1675873624:

===========================================================
Deprecation: #99882 - Site Language "typo3Language" setting
===========================================================

See :issue:`99882`

Description
===========

A language configuration defined for a site has had various settings, one of
them being "typo3Language". The setting is used to define the language key which
should be used for fetching the proper XLF file (such as :file:`de_AT.locallang.xlf`).

Since TYPO3 v12 it is not needed to set this property in the site configuration
anymore, and is removed from the Backend UI. The information is now automatically
derived from the Locale setting of the Site Configuration.

The previous value "default", which matched "en" as language key is now not
necessary anymore as "default" is a synonym for "en" now.

As a result, the amount of options in the User Interface for integrators is
reduced.


Impact
======

An Administrator can not select a value for the "typo3Language" setting anymore
via the TYPO3 Backend, if a custom value is required, the Site Configuration
needs to be manually edited and the "typo3Language" setting needs to be added.

If this is the case, please file a bug report in order to give the TYPO3
development team feedback what use-case is required.

However, when saving a site configuration via the TYPO3 Backend will still
keep the "typo3Language" setting so no values are lost.


Affected installations
======================

TYPO3 installations which have been created before TYPO3 v12.3.


Migration
=========

No migration is needed as the explicit option is still evaluated. It is however
recommended to evaluate if the setting is really needed.

Examples:

1) If "typo3Language=default" and "locale=en_US.UTF-8", the setting can be removed.
2) If "typo3Language=pt_BR" and "locale=pt_BR.UTF-8", the setting can be removed.
3) If "typo3Language=de" and "locale=de_AT.UTF-8", the setting can be removed, plus the
label files check for "de_AT.locallang.xlf" and "de.locallang.xlf" as fallback when accessing
a translated label.
4) If "typo3Language=pt_BR" and "locale="de_DE.UTF-8" it is likely a misconfiguration
in the setup, and should be analyzed if the custom value is really needed.

.. index:: YAML, NotScanned, ext:core
