.. include:: /Includes.rst.txt

===============================================================================
Breaking: #93110 - Indexed search does not provide hook for EXT:crawler anymore
===============================================================================

See :issue:`93110`

Description
===========

Indexed search had an explicit dependency on an old API of
the third-party extension "crawler". This cross-dependency did
not allow either component to move forward.

In order to build a new solution, legacy code has been removed
without substitution for the time being, where as new code
will be added during further TYPO3 v11 development.


Impact
======

TYPO3 v11 does not use existing EXT:crawler hooks and APIs anymore.


Affected Installations
======================

TYPO3 installations using EXT:crawler and EXT:indexed_search.


Migration
=========

None until a more flexible solution is provided, however
this only affects the maintainers of EXT:crawler.

.. index:: PHP-API, NotScanned, ext:indexed_search
