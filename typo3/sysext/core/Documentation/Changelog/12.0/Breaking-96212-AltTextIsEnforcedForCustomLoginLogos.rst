.. include:: /Includes.rst.txt

.. _breaking-96212:

==============================================================
Breaking: #96212 - Alt text is enforced for custom login logos
==============================================================

See :issue:`96212`

Description
===========

To improve the accessibility of the login screen, the :html:`alt` attribute
has been added to the login logo in :issue:`92628`. In case installations use
a custom login logo, configured in :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['backend']['loginLogo']`,
it had also been possible to add a corresponding "alt" text for it with
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['backend']['loginLogoAlt']`.

In case a custom logo was used, but no custom "alt" text configured, the
:html:`alt` attribute was omitted. This has changed. The :html:`alt`
attribute is now always added to the login logo. In case a custom logo is
used, but no custom "alt" text defined, TYPO3 now automatically falls back
to a default "alt" text.

Impact
======

The :html:`alt` attribute is now enforced for the login logo.

Affected Installations
======================

All installations using a custom login logo, while not defining a
corresponding "alt" text.

Migration
=========

Add a corresponding "alt" text with
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['backend']['loginLogoAlt']`.

.. note::

    Those settings are also available in the backend extension configuration
    :guilabel:`Admin Tools -> Settings -> Configure extensions -> backend`

.. index:: Backend, LocalConfiguration, NotScanned, ext:backend
