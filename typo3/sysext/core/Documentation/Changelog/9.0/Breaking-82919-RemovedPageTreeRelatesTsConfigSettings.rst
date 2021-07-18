.. include:: ../../Includes.txt

=============================================================
Breaking: #82919 - Removed pagetree-related TSconfig settings
=============================================================

See :issue:`82919`

Description
===========

The following edge-case TSconfig options have been removed:

-  :typoscript:`options.pageTree.disableIconLinkToContextmenu` (Icons are always linked)
-  :typoscript:`options.pageTree.searchInAlias` (the pages.alias DB field is now always respected when filtering)
-  :typoscript:`options.pageTree.excludeDoktypes` (there is no restriction to doctypes in the filter anymore)
-  :typoscript:`options.pageTree.hideFilter` (filter is now always visible)


Impact
======

Setting these options in UserTSconfig will have no effect anymore.


Affected Installations
======================

Installations having one of these options set.

.. index:: Backend, TSConfig, NotScanned
