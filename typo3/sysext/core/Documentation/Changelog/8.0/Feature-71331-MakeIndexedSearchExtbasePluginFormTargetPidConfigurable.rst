
.. include:: ../../Includes.txt

=================================================================================
Feature: #71331 - Make indexed_search extbase plugin form target Pid configurable
=================================================================================

See :issue:`71331`

Description
===========

The search form target page of the extbase variant of EXT:indexed_search can now be
configured by using the TypoScript option `plugin.tx_indexedsearch.settings.targetPid = 123`.

If it is empty, the current page will be used.

.. index:: Frontend, ext:indexed_search, TypoScript