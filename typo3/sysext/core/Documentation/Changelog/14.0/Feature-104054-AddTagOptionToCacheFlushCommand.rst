.. include:: /Includes.rst.txt

.. _feature-104054-1754603481:

===============================================
Feature: #104054 - Add cache flush tags command
===============================================

See :issue:`104054`

Description
===========

A new command `cache:flushtags` has been introduced to allow flushing cache
entries by tag.

Multiple tags can be flushed by passing a comma-separated list of tags.
It is also possible to flush tags for a specific cache group by using the
`--groups` or `-g` option. If no group is specified, all cache groups
are considered.

Note that certain combinations of groups and tags do not make sense,
specifically the `di` and `system` cache groups.

Examples
--------

..  code-block:: bash
    :caption: Example command usage (Composer mode projects)

    vendor/bin/typo3 cache:flushtags pageId_123
    vendor/bin/typo3 cache:flushtags pages_100,pages_200
    vendor/bin/typo3 cache:flushtags tx_news -g pages

Impact
======

It is now possible to flush cache entries for specific tag and group
combinations directly from the command line.

.. index:: CLI, ext:core
