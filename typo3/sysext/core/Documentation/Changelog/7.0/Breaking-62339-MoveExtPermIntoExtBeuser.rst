
.. include:: ../../Includes.txt

====================================================================
Breaking: #62339 - Move EXT:perm into EXT:beuser and remove EXT:perm
====================================================================

See :issue:`62339`

Description
===========

The extension EXT:perm is removed from core, the perms module is moved into EXT:beuser.
The BE module moved from "Web Access" to "System Access"


Impact
======

Extensions that use EXT:perm or maybe depends on it will cause problems


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses parts the removed extension.


Migration
=========

The logic is moved into EXT:beuser. No special migration is necessary.


.. index:: PHP-API, Backend, ext:beuser
