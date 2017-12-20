.. include:: ../../Includes.txt

======================================================================
Breaking: #82701 - Always consider publishing references in workspaces
======================================================================

See :issue:`82701`

Description
===========

The TSconfig option :ts:`options.workspaces.considerReferences` to disable references when publishing
records from a workspace has been removed.

The according method :php:`TYPO3\CMS\Version\DataHandler\CommandMap::setWorkspacesConsiderReferences()`
has been removed.


Impact
======

Disabling this setting will have no effect anymore, thus publishing records will always
publish relations e.g. in IRRE relations as well.

Calling the removed PHP method will throw a PHP fatal error.


Affected Installations
======================

Installations with workspaces enabled, having the TSconfig option explicitly disabled.


Migration
=========

Remove any calls to the method, as it has no effect anymore.

.. index:: TSConfig, PartiallyScanned, ext:workspaces
