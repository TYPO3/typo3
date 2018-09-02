.. include:: ../../Includes.txt

==========================================================================
Feature: #85164 - Available languages respects site configuration settings
==========================================================================

See :issue:`85164`

Description
===========

When the backend shows the list of available languages - for instance in the page module
language selector, when editing records and in the list module - the list of languages
is now restricted to those defined by the site module.

If there are for instance five language records in the system, but a site configures
only three of them for a page tree, only those three are considered when rendering
language drop downs.

In case no site configuration has been created for a tree, all language records are shown. In
this case the Page TSconfig options :typoscript:`mod.SHARED.defaultLanguageFlag`,
:typoscript:`mod.SHARED.defaultLanguageLabel` and :typoscript:`mod.SHARED.disableLanguages` settings
are also considered - those are obsolete if a site configuration exists.

.. index:: Backend, ext:backend
