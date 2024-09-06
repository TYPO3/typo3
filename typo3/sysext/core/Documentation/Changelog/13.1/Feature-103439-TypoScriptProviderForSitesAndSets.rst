.. include:: /Includes.rst.txt

.. _feature-103439-1712321631:

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


Impact
======

Sites and sets can ship TypoScript without the need for :sql:`sys_template`
records in database, and dependencies can be expressed via sets, allowing for
automatic ordering and deduplication.

.. index:: Backend, Frontend, PHP-API, TypoScript, YAML, ext:core
