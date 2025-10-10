..  include:: /Includes.rst.txt

..  _breaking-107654-1728554192:

====================================================================
Breaking: #107654 - Remove random subpage option of doktype=shortcut
====================================================================

See :issue:`107654`

Description
===========

The "random subpage" option for shortcut pages has been removed from TYPO3 Core.
This option allowed shortcut pages to redirect to a random subpage, which
was problematic for caching and resulted in unpredictable behavior - a "random"
page was not truly random as the page that linked to this shortcut page was
cached.

The following changes have been made:

* The class constant
  :php:`TYPO3\CMS\Core\Domain\Repository\PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE`
  has been removed.

* The method signature of
  :php:`TYPO3\CMS\Core\Domain\Repository\PageRepository::resolveShortcutPage()`
  has changed from
  :php:`resolveShortcutPage(array $page, bool $resolveRandomSubpages = false, bool $disableGroupAccessCheck = false)`
  to
  :php:`resolveShortcutPage(array $page, bool $disableGroupAccessCheck = false)`.

* The TCA configuration for :sql:`pages.shortcut_mode` no longer includes
  the "Random subpage of selected/current page" option.


Impact
======

Code using the removed constant :php:`TYPO3\CMS\Core\Domain\Repository\PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE` will fail
with a PHP fatal error.

Code calling :php:`PageRepository::resolveShortcutPage()` with three parameters
where the second parameter was :php:`$resolveRandomSubpages` will fail, as this
parameter has been removed and the second parameter is now :php:`$disableGroupAccessCheck`.

Shortcut pages configured to use "random subpage" mode in the database will now
behave as if they were configured for "first subpage" mode.


Affected installations
======================

TYPO3 installations with:

* Extensions using the constant :php:`PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE`
* Extensions calling :php:`PageRepository::resolveShortcutPage()` with the
  :php:`$resolveRandomSubpages` parameter
* Extensions extending :php:`PageRepository` and overriding :php:`getPageShortcut()`
* Shortcut pages configured with "random subpage" mode in the database


Migration
=========

Remove any usage of :php:`PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE`.

Update calls to :php:`PageRepository::resolveShortcutPage()` to remove the
:php:`$resolveRandomSubpages` parameter:

..  code-block:: php
    :caption: Before (TYPO3 v13 and lower)

    $page = $pageRepository->resolveShortcutPage($page, false, true);

..  code-block:: php
    :caption: After (TYPO3 v14+)

    $page = $pageRepository->resolveShortcutPage($page, true);

For shortcut pages configured with "random subpage" mode, update the database
records to use a different shortcut mode (e.g., "first subpage" or specify
a target page directly).

..  code-block:: sql
    UPDATE pages SET shortcut_mode=1 WHERE shortcut_mode=2;

..  index:: PHP-API, TCA, Frontend, FullyScanned, ext:core
