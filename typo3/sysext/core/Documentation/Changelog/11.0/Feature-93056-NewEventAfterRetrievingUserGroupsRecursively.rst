.. include:: /Includes.rst.txt

====================================================================
Feature: #93056 - New Event after retrieving user groups recursively
====================================================================

See :issue:`93056`

Description
===========

When user groups are loaded, for example when a backend editors groups and permissions
are calculated, a new PSR-14 event :php:`AfterGroupsResolvedEvent` is fired.


Impact
======

This Event contains a list of retrieved groups from the database, which can
be modified (e.g. adding more groups when a particular user or a user from a
given location is logged in) via Event listeners.

This event acts as a substitution for the removed TYPO3 Hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['fetchGroups_postProcessing']`.

.. index:: PHP-API, ext:backend
