.. include:: /Includes.rst.txt

.. _breaking-97530-1651500260:

===================================================================================
Breaking: #97530 - Indexed Search option searchSkipExtendToSubpagesChecking removed
===================================================================================

See :issue:`97530`

Description
===========

The TypoScript property :typoscript:`searchSkipExtendToSubpagesChecking`
related to Indexed Search query results has been removed.

Setting the option made Indexed Search bypass the check for validating pages
related to TYPO3's :php:`extendToSubpages` Core feature. However, since the
:php:`extendToSubpages` functionality has now been optimized via an alternative
to :php:`getTreeList()`, the option is removed.

Impact
======

Setting the option
:typoscript:`plugin.tx_indexedsearch.settings.searchSkipExtendToSubpagesChecking`
has no effect anymore.

All search requests within indexed search will now respect the
:php:`extendToSubpages` flag.

Affected installations
======================

TYPO3 installations using Indexed Search having this option set.

Migration
=========

If you still encounter using indexed search related to :php:`extendToSubpages` it is
recommended to extend Indexed Search queries with custom hooks to manipulate
the search query.

.. index:: TypoScript, NotScanned, ext:indexed_search
