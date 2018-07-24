.. include:: ../../Includes.txt

===========================================================
Important: #85164 - Allow to only show site languages in BE
===========================================================

See :issue:`85164`

Description
===========

When the backend shows list of available languages - for instance in the page module
language selector, when editing records and in the list module - the list of languages
is now restricted to those defined by the site module.

If there are for instance five language records in the system, but a site configures
only three of them for a page tree, only those three are considered when rendering
language drop downs.

In case no site configuration has been created for a tree, all language records are shown. In
this case the Page TSconfig options :php:`mod.SHARED.defaultLanguageFlag`,
:php:`mod.SHARED.defaultLanguageLabel` and :php:`mod.SHARED.disableLanguages` settings
are also considered - those are obsolete if a site configuration exists.

.. index:: Backend, ext:backend
