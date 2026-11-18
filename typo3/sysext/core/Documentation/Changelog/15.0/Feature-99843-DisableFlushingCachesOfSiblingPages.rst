..  include:: /Includes.rst.txt

..  _feature-99843-1795040341:

==========================================================
Feature: #99843 - Disable flushing caches of sibling pages
==========================================================

See :issue:`99843`

Description
===========

When a page is changed, the caches of the page itself, its parent page and
all pages on the same level (its siblings) are flushed. In page trees with
many pages on one level, every change of a page flushes the caches of all
of them, including all their translations.

The new Page TSconfig option :typoscript:`TCEMAIN.clearCache_pageSiblings`
allows to turn off flushing the caches of sibling pages. It is enabled by
default.

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/page.tsconfig

    TCEMAIN.clearCache_pageSiblings = 0

With this setting, changing a page flushes the caches of the page itself, its
translations and its parent page only.
:typoscript:`TCEMAIN.clearCache_pageSiblingChildren` then flushes the
subpages of the changed page, and
:typoscript:`TCEMAIN.clearCache_pageGrandParent` keeps flushing the page above
the parent page.

The option is respected by
:php:`\TYPO3\CMS\Core\Cache\Flush\CacheFlusher` and thus by
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` and any other code
flushing the caches of a changed page.

Impact
======

Integrators can reduce the number of page caches flushed when editing pages
in large page trees, which speeds up saving pages and keeps the caches of
unrelated pages intact.

..  index:: Backend, TSConfig, ext:core
