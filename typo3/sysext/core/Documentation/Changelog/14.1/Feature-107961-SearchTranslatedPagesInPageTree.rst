..  include:: /Includes.rst.txt

..  _feature-107961-1762076523:

=======================================================
Feature: #107961 - Search translated pages in page tree
=======================================================

See :issue:`107961`

Description
===========

The page tree filter has been extended with the ability to search for pages
through their translated content. This enhancement makes it significantly easier
to find pages in multilingual TYPO3 installations, particularly when editors
work primarily with translated content.

The page tree filter now supports two translation search methods:

1. **Search by translated page title**: When a search phrase matches a translated page
   title or nav_title, the corresponding default language page will be found and
   displayed in the page tree.

2. **Search by translation UID**: When searching for a numeric page UID that belongs
   to a translated page, the parent default language page will be found and displayed.

Both search methods work seamlessly alongside the existing search capabilities
(searching by page title, nav_title, or default language UID).

Configuration
=============

Translation search is enabled by default and can be controlled in two ways:

User TSconfig
-------------

Administrators can control the availability of translation search via User TSconfig:

..  code-block:: typoscript

    # Disable searching in translated pages for specific users/groups
    options.pageTree.searchInTranslatedPages = 0

User Preference
---------------

Individual backend users can toggle this setting using the page tree toolbar menu.
The preference is stored in the backend user's configuration, allowing each user
to customize their search behavior.

Visual Feedback
===============

When a page is found through a translation match, a colored label is automatically
added to provide clear visual feedback:

**Single translation match**
    Displays "Found in translation: [Language Name]"

    Example: When searching for "Produkte", a page found via its German translation
    shows "Found in translation: German"

**Multiple translation matches**
    Displays "Found in multiple translations"

    Example: When searching for "Home", a page with matching French and German
    translations shows "Found in multiple translations"

**Direct matches**
    Pages matching the search phrase directly (L=0) show "Search result"

    Example: When searching for "Products", the English page titled "Products"
    shows "Search result"

**Combined matches**
    When a page matches both directly and through a translation, both labels
    are displayed.

    Example: Searching for "Home" finds a page titled "Home" with a German
    translation "Startseite Home" - the page shows both labels.


.. note::

    Search term highlighting works only for direct matches. For performance
    reasons, it is activated only if the search yields fewer than 100 results,
    and it requires the user to enter at least two characters or a number.

Flexibility for developers
==========================

Developers can still use the PSR-14 event
:php:`\TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent`
to add custom labels or modify the prepared tree items before they are rendered.

Impact
======

Editors working in multilingual TYPO3 installations can now efficiently search
for pages using translated titles or translation UIDs. The visual labels provide
immediate feedback about how search results were matched, improving the user
experience when navigating complex page trees.

The feature respects user permissions (language restrictions from user groups)
and workspace context, ensuring that only accessible translations are searched.

..  index:: Backend, ext:backend
