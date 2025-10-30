..  include:: /Includes.rst.txt

..  _feature-103258-1709588499:

==================================================
Feature: #103258 - Language filter for list module
==================================================

See :issue:`103258`

Description
===========

The list module now provides a language filter in the document header, similar
to the existing language selector in the page module. This allows backend users
to filter records by language, making it easier to focus on content in a
specific language when working with multilingual websites.

The language filter appears as a dropdown button in the document header toolbar
and provides the following options:

*   All available site languages that have page translations
*   "All languages" (if translations exist on the current page)

Key Behaviors
-------------

Language Selection Persistence
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The selected language is stored in the backend user's module data and persists
across page navigation. When switching between pages, the previously selected
language remains active if available on the new page.

Automatic Fallback
~~~~~~~~~~~~~~~~~~

If navigating to a page where the selected language is not available (no
translation exists), the list module automatically falls back to:

*   "All languages" mode (if the page has any translations)
*   Default language (if the page has no translations at all)

This fallback is temporary and does not overwrite the language preference.
When navigating to a page with the selected language translation, it will
automatically be restored.

Display Behavior
~~~~~~~~~~~~~~~~

When a specific language is selected:

*   Records in the selected language are displayed
*   Default language records (language 0) are always included as fallback
*   Records with "all languages" flag (-1) are included

Example: If French is selected, one will see French translations, default
language content, and content marked for "all languages".

Localization Restrictions
~~~~~~~~~~~~~~~~~~~~~~~~~

The localization panel respects page translation availability. When a language
is selected or when viewing in "all languages" mode, the list module only
offers localization options for languages where the page has an existing
translation.

This ensures data integrity by preventing the creation of records in
languages where the parent page does not exist.

Impact
======

Backend users can now efficiently filter list module records by language,
improving the workflow when managing multilingual content.

The language selection persists across page navigation, reducing the need to
repeatedly select the same language. The intelligent fallback mechanism ensures
the list module always displays relevant content, even when switching between
pages with different translation availability.

This enhancement brings the list module's language handling in line with the
page module, providing a consistent user experience across TYPO3's backend.

..  index:: Backend, ext:backend
