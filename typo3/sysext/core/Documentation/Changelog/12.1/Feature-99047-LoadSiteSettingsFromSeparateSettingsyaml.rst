.. include:: /Includes.rst.txt

.. _feature-99047-1668081474:

================================================================
Feature: #99047 - Load site settings from separate settings.yaml
================================================================

See :issue:`99047`

Description
===========

Site settings have been introduced with TYPO3 v10 as part of the configuration
of a site. In contrast to the site configuration, they are mostly used to
provide sane defaults for TypoScript constants and have a layer of arbitrary
configuration available in any context.

In order to separate these settings from the system site configuration, make them
accessible and editable in the TYPO3 backend, and to distinguish between required
site configuration and optional settings, the "settings" part of the settings are
moved to a separate :file:`settings.yaml` file in the site configuration folder.

A migration wizard is provided as upgrade wizard to migrate settings into the
new file.


Impact
======

Settings are now loaded from a separate file called :file:`settings.yaml` residing
next to the :file:`config.yaml` of a site.
Executing the upgrade wizard will load all settings of a site and create that
file for the user. The migration wizard will not remove / rewrite the
:file:`config.yaml` - the user should do that on their own, to avoid breaking
custom-built functionality.

.. index:: Backend, YAML, ext:core
