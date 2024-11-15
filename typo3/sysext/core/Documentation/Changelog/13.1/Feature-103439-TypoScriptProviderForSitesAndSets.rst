..  include:: /Includes.rst.txt

..  _feature-103439-1712321631:

=========================================================
Feature: #103439 - TypoScript provider for sites and sets
=========================================================

See :issue:`103439`

Description
===========

TYPO3 sites have been enhanced to be able to operate as TypoScript template
provider. They act similar to :sql:`sys_template` records with "clear" and "root"
flags set. By design a site TypoScript provider always defines a new scope
("root" flag) and does not inherit from parent sites (for example, sites up in the
root line). That means it behaves as if the "clear" flag is set in a `sys_template`
record. This behavior is not configurable by design, as TypoScript code sharing
is intended to be implemented via sharable sets (:ref:`feature-103437-1712062105`).

Note that :sql:`sys_template` records will still be loaded, but they are optional
now, and applied after TypoScript provided by the site.

TypoScript dependencies can be included via set dependencies. This mechanism is
much more effective than the previous static_file_include's or manual :typoscript:`@import`
statements (they are still fine for local includes, but should be avoided for
cross-set/extensions dependencies), as sets are automatically ordered and
deduplicated.


Site TypoScript
---------------

The files :file:`setup.typoscript` and :file:`constants.typoscript` (placed next
to the site's :file:`config.yaml` file) will be loaded as TypoScript setup and
constants, if available.

Site dependencies (sets) will be loaded first, that means setup and constants
can be overridden on a per-site basis.


Set TypoScript
--------------

Set-defined TypoScript can be shipped within a set. The files
:file:`setup.typoscript` and :file:`constants.typoscript` (placed next to the
:file:`config.yaml` file) will be loaded, if available.
They are inserted (similar to `static_file_include`) into the TypoScript chain
of the site TypoScript that will be defined by a site that is using sets.

Set constants will always be overruled by site settings. Since site settings
always provide a default value, a constant will always be overruled by a defined
setting. This can be used to provide backward compatibility with TYPO3 v12
in extensions, where constants shall be used in v12, while v13 will always
prefer defined site settings.

In contrast to `static_file_include`, dependencies are to be included via
sets. Dependencies are included recursively. This mechanism supersedes the
previous include via `static_file_include` or manual :typoscript:`@import` statements as
sets are automatically ordered and deduplicated. That means TypoScript will not
be loaded multiple times, if a shared dependency is required by multiple sets.

Note that :typoscript:`@import` statements are still fine to be used for local
includes, but should be avoided for cross-set/extensions dependencies.


..  _global_typoscript_in_site_sets:
Global TypoScript
-----------------

Site sets introduce reliable dependencies in order to replace the need for
globally provided TypoScript. It is therefore generally discouraged to use
global TypoScript in an environment using TypoScript provided by site sets.
TypoScript should only be provided globally if absolutely needed.

It has therefore been decided that :file:`ext_typoscript_setup.typoscript` and
:file:`ext_typoscript_constants.typoscript` are not autoloaded in site set
provided TypoScript.

These files can still be used to provide global TypoScript for traditional
:sql:`sys_template` setups. Existing setups do not need to be adapted and
extensions can still ship globally defined TypoScript via
:file:`ext_typoscript_setup.typoscript` for these cases, but should provide
explicitly dependable sets for newer site set setups.

If global TypoScript is still needed and is unavoidable, it can be provided
for site sets and :sql:`sys_template` setups in :file:`ext_localconf.php` via:

..  code-block:: php

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
        'module.tx_foo.settings.example = 1'
    );


There are some cases where globally defined TypoScript configurations are needed
because backend modules rely on their availability. One such case is the form
framework backend module which uses
:typoscript:`module.tx_form.settings.yamlConfigurations` as a registry for
extension-provided form configuration. Global form configuration can be loaded
via :php-short:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility` as described
in
:ref:`YAML registration for the backend via addTypoScriptSetup() <typo3/cms-form:concepts-configuration-yamlregistration-backend-addtyposcriptsetup>`
Please make sure to only load backend-related form TypoScript globally and to
provide TypoScript related to frontend rendering via site sets.


Impact
======

Sites and sets can ship TypoScript without the need for :sql:`sys_template`
records in database, and dependencies can be expressed via sets, allowing for
automatic ordering and deduplication.

.. index:: Backend, Frontend, PHP-API, TypoScript, YAML, ext:core
