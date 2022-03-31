.. include:: /Includes.rst.txt

====================================================================
Breaking: #93056 - Removed hooks when retrieving Backend user groups
====================================================================

See :issue:`93056`

Description
===========

When the user groups of a backend user are loaded, two hooks
(before and after fetching) were in place to modify the
list of groups.

:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['fetchGroupQuery']`
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['fetchGroups_postProcessing']`

This functionality is replaced by a new PHP :php:`GroupResolver` class,
the hooks have been removed, and a new Event has been added instead.


Impact
======

Using those hooks has no effect anymore, as the hooks are never called in TYPO3 v11.


Affected Installations
======================

TYPO3 installations with custom extensions using these hooks,
which is usually around enhancing the permission system or custom
group resolving.


Migration
=========

When user groups are loaded, for example when a backend editors' groups and permissions
are calculated, a new PSR-14 event :php:`AfterGroupsResolvedEvent` is fired.

The hooks have been removed without deprecation in order to allow
extensions to make their extension compatible with TYPO3 v10 (using the hooks),
and TYPO3 v11 (use the PSR-14 instead).

.. index:: PHP-API, FullyScanned, ext:backend
