..  include:: /Includes.rst.txt

..  _important-108796-1738078801:

====================================================================
Important: #108796 - Internal shortcut classes renamed to bookmark
====================================================================

See :issue:`108796`

Description
===========

As part of the centralized bookmark management feature, several internal classes
related to backend shortcuts have been renamed to use "bookmark" terminology.
These classes were marked as :php:`@internal` and are not part of the public
TYPO3 API. However, some extensions might have used them despite being internal.

The following classes have been renamed or replaced:

:php:`\TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository`
    Removed and replaced by
    :php:`\TYPO3\CMS\Backend\Backend\Bookmark\BookmarkService` and
    :php:`\TYPO3\CMS\Backend\Backend\Bookmark\BookmarkRepository`.

:php:`\TYPO3\CMS\Backend\Controller\ShortcutController`
    Renamed to :php:`\TYPO3\CMS\Backend\Controller\BookmarkController`.

:php:`\TYPO3\CMS\Backend\Backend\ToolbarItems\ShortcutToolbarItem`
    Renamed to :php:`\TYPO3\CMS\Backend\Backend\ToolbarItems\BookmarkToolbarItem`.

The JavaScript module :js:`@typo3/backend/toolbar/shortcut-menu` has been removed
and replaced by the new bookmark management modules in
:js:`@typo3/backend/bookmark/`.

..  index:: Backend, JavaScript, PHP-API, ext:backend
