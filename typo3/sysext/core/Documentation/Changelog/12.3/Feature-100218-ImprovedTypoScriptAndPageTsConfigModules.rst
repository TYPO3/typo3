.. include:: /Includes.rst.txt

.. _feature-100218-1679312518:

===============================================================
Feature: #100218 - Improved TypoScript and PageTsConfig modules
===============================================================

See :issue:`100218`

Description
===========

TYPO3 v12 comes with a rewritten TypoScript syntax parser.
See :ref:`breaking-97816-1656350406` and :ref:`feature-97816-1656350667`
for more details on this.

The new parser allowed us to refactor the related Backend modules along the way:
While many of these have been done with earlier v12 releases already, 12.3 now
finishes the basic feature set of these new and refactored modules.

This is a summary of these UI changes:

Frontend TypoScript
-------------------

* The well-known main module "Web > Template" has been renamed and moved and can
  be found as "Site Management > TypoScript".

* "TypoScript records overview": This submodule was more hidden in previous versions.
  It gives an overview which page records have TypoScript template records.

* "Constant Editor": This submodule is mainly kept as-is from previous versions.

* "Edit TypoScript Record": This submodule was known as "Info / Modify" from previous
  versions. It's main functionality is kept.

* "Active TypoScript": This submodule was known as "TypoScript Object Browser" in
  previous versions. The UI of this module received a major streamlining and gives
  a better overview of the compiled TypoScript on a page: The module now shows
  both "constants" and "setup" at the same time, gives more detail information,
  and the tree is quicker to navigate.

* "Included TypoScript": This submodule was known as "Template Analyzer" in
  previous versions. Similar to "Active TypoScript", it shows "constants" and
  "setup" at the same time. It allows to simulate the effect of conditions
  to the include tree, and shows sub-includes from :typoscript:`@import` and
  similar as nodes within the tree. A basic syntax scanner finds broken TypoScript
  syntax snippets.


PageTsConfig
------------

* The previous submodule "Web > Info > Page TsConfig" has been heavily refactored
  and can be found as new main module "Site Management > PageTsConfig".

* The new PageTsConfig module is similar in it's look and feel to the TypoScript
  module.

* "PageTsConfig Records": This sub modules did not exist as such in previous versions
  and gives an overview which page records in the system contain PageTsConfig settings.

* "Active PageTsConfig": This is similar to "Active TypoScript" from the "TypoScript"
  module. It allows browsing current PageTsConfig and allows simulating the effect
  of conditions.

* "Included PageTsConfig": This is similar to the "Included TypoScript" from the
  "TypoScript" module. It shows all source files and records that create the final
  PageTsConfig of a page. A basic syntax scanner finds broken syntax snippets.

Impact
======

The refactored modules allow more fine grained analysis
of `TsConfig` and `TypoScript`.

.. index:: Backend, TSConfig, TypoScript, ext:backend
