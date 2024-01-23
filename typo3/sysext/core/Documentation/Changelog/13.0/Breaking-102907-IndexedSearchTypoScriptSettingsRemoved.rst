.. include:: /Includes.rst.txt

.. _breaking-102907-1706043066:

==============================================================
Breaking: #102907 - Indexed Search TypoScript settings removed
==============================================================

See :issue:`102907`

Description
===========

Indexed Search previously used a custom link building to generate links and their targets
for linking to search results that are of type "page", instead of the native "typolink" (:php:`LinkFactory`)
system, automatically detecting links to other sites of the same installation and using
the proper :typoscript:`extTarget` setting in TypoScript for creating the target attribute for the link.

For this reason, the two TypoScript settings are removed:

.. code-block:: typoscript

    plugin.tx_indexedsearch.settings.detectDomainRecords
    plugin.tx_indexedsearch.settings.detectDomainRecords.target


Impact
======

Setting these options have no effect anymore.


Affected installations
======================

TYPO3 installations using indexed search using these options.


Migration
=========

Remove the lines, and adapt config.extTarget accordingly if needed in such cases, as
the links are now generated through TYPO3's native link building APIs.

.. index:: TypoScript, NotScanned, ext:indexed_search
