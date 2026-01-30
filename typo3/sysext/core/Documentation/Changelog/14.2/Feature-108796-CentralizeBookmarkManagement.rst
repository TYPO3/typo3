..  include:: /Includes.rst.txt

..  _feature-108796-1738078800:

=================================================
Feature: #108796 - Centralize Bookmark Management
=================================================

See :issue:`108796`

Description
===========

The TYPO3 backend bookmark system has been comprehensively overhauled,
introducing a centralized architecture that replaces the legacy "shortcut"
implementation.

Bookmark Groups
---------------

Bookmarks can be organized into three types of groups.

System groups are defined via UserTSconfig using
:typoscript:`options.bookmarkGroups` and are available to all users. Global
groups contain bookmarks visible to all backend users, though only
administrators can add bookmarks to these groups. User groups are custom groups
created by individual users for personal organization, stored in a new database
table :sql:`sys_be_shortcuts_group`.

Five default bookmark groups are provided out of the box: Pages, Records,
Files, Tools, and Miscellaneous. Previously these groups were hardcoded in PHP,
but they are now defined via UserTSconfig in EXT:backend, making them fully
customizable. The functionality remains the same, but administrators now have
complete control over which groups are available.

The :typoscript:`options.bookmarkGroups` setting should only be modified on a
global scope and not on a per-user basis, as inconsistent group configurations
between users can lead to unexpected behavior:

..  code-block:: typoscript
    :caption: EXT:my_ext/Configuration/user.tsconfig

    # Remove a specific default group (e.g., Files)
    options.bookmarkGroups.3 >

    # Remove all default groups
    options.bookmarkGroups >

    # Add a custom group with a static label
    options.bookmarkGroups.10 = My Custom Group

    # Add a custom group with a translatable label using domain syntax
    options.bookmarkGroups.11 = my_extension.messages:bookmark_group.custom

    # Disable bookmarks entirely
    options.enableBookmarks = 0

Group labels support the TYPO3 translation domain syntax, allowing extensions
to provide translated group names. The format is
:typoscript:`extension_key.messages:translation_key`, which resolves to the default
language file at :file:`EXT:extension_key/Resources/Private/Language/locallang.xlf`.

As before, group ID -100 has special behavior as a superglobal group. Bookmarks
assigned to this group are visible to all backend users, but only administrators
can add or modify bookmarks in this group. This allows administrators to provide
a shared set of bookmarks across the entire TYPO3 installation.

Bookmark Manager
----------------

A new modal-based :guilabel:`Bookmark Manager` provides a centralized interface
for managing all bookmarks. The manager supports drag and drop reordering to
reorganize bookmarks within and across groups. Bulk operations allow selecting
multiple bookmarks to move or delete at once. Users can create, edit, and
delete custom bookmark groups through the group management interface, and
rename bookmarks directly through inline editing.

The Bookmark Manager can be accessed via the bookmark icon in the toolbar
dropdown menu.

Impact
======

The bookmark toolbar item now opens a dropdown with quick access to recent
bookmarks and a link to the full Bookmark Manager. Users can create custom
bookmark groups for better organization of their saved pages, records, and
modules. Administrators can configure global bookmarks visible to all users.
The Bookmarks dashboard widget has been updated to support the new bookmark
system with group filtering and limit options. The legacy "shortcut"
terminology has been replaced with "bookmark" throughout the backend
interface and codebase.

..  index:: Backend, TSConfig, ext:backend
