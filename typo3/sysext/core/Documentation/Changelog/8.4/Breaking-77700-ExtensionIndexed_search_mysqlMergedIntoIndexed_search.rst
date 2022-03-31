.. include:: /Includes.rst.txt

============================================================================
Breaking: #77700 - Extension indexed_search_mysql merged into indexed_search
============================================================================

See :issue:`77700`

Description
===========

The extension `indexed_search_mysql` has been removed and its functionality was merged into
EXT:`indexed_search`. The `MySQL` specific search based on fulltext indexes can
be enabled with a feature flag within the extension configuration of the extension manager.


Impact
======

If extension `indexed_search_mysql` has been loaded, the feature flag `useMysqlFulltext`
within the extension configuration of the ExtensionManager `indexed_search` has to be
set, otherwise `indexed_search` falls back to the potentially slower non-fulltext based
default search algorithm.


Affected Installations
======================

Instances with extension `indexed_search_mysql` loaded.


Migration
=========

Full functionality can be kept by enabling the feature toggle `useMysqlFulltext` within
the extension configuration of `indexed_search`.

.. index:: LocalConfiguration, ext:indexed_search, ext:indexed_search_mysql
