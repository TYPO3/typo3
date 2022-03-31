.. include:: /Includes.rst.txt

========================================================================
Breaking: #92993 - Generic search statistics from indexed search removed
========================================================================

See :issue:`92993`

Description
===========

When using TYPO3 Cores built-in Frontend Search ("Indexed Search"), search
statistics were written which were never evaluated, but might contain
user-specific information about logged-in users and their previously used sessions,
which might be conflicting with privacy policies.

The IP Address could be masked via Indexed Search Extension Setting
:php:`trackIpInStatistic` which is now removed, along the database table
:sql:`index_search_stat`.


TYPO3 also stores statistics on the searched word, which is evaluated
in the TYPO3 Backend, and kept.


Impact
======

Searching within Indexed Search will only track the searched words, but not
additional meta data anymore.

The database table :sql:`index_search_stat` is not available anymore, along with the
Extension setting to disable IP address tracking, as nothing is tracked anymore.


Affected Installations
======================

TYPO3 installations using Indexed Search and accessing this information.


Migration
=========

It is recommended to use a more generic and sophisticated analytics tool like
Matomo or Google Analytics to track searched terms.

.. index:: Database, NotScanned, ext:indexed_search
