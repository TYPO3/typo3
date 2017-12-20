
.. include:: ../../Includes.txt

===========================================================================
Breaking: #62415 - Remove deprecated disable_autocreate field in workspaces
===========================================================================

See :issue:`62415`

Description
===========

The field "disable_autocreate" from ext:workspaces has been removed.

Impact
======

If a 3rd party extension relies on the removed field an SQL error will be thrown.


Affected installations
======================

An installation is affected if a 3rd party extension relies on the removed field in the database.

Migration
=========

Remove any usage of the removed field in 3rd party extensions.


.. index:: Database, Backend, ext:workspaces
