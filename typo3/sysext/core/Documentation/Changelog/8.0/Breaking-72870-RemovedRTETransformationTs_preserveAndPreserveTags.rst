
.. include:: ../../Includes.txt

==========================================================================
Breaking: #72870 - Removed RTE transformation ts_preserve and preserveTags
==========================================================================

See :issue:`72870`

Description
===========

The RTE configuration TSconfig option `RTE.default.proc.preserveTags` to preserve special tags has been removed.

The RTE transformation mode "ts_preserve" to change special preserved tags and migrate to <span> tags has been removed.

The according methods `TS_preserve_db` and `TS_preserve_rte` within RteHtmlParser have been removed.


Impact
======

Setting the TSconfig option or the RTE transformation mode has no effect anymore.

Calling the removed PHP methods directly will result in fatal PHP errors.


Affected Installations
======================

TYPO3 instances with custom RTE transformations using the removed "ts" transformation mode, or a custom transformation mode.


Migration
=========

Use the RTE processing option `RTE.default.proc.allowTags` to include the tags without rewriting them to custom <span> tags.

If special handling is still necessary, an existing hook can be used to re-implement the logic.

.. index:: TSConfig, Backend, RTE
