.. include:: /Includes.rst.txt

============================================================================
Important: #92336 - Discarding records in workspace module hard deletes them
============================================================================

See :issue:`92336`

Description
===========

The discard functionality in the workspace module allows to "throw away"
changes that have been done by editors in a workspace.

On database side, discard previously created a mixture of hard deleted (dropped)
rows and soft deleted (field :sql:`deleted` set to :sql:`1`) rows.

This has been streamlined: Discarding records now always hard deletes rows from
the database. Those records can't be "undeleted" using the recycler extension
anymore, which only worked in very simple and limited cases before.

.. index:: Backend, Database, ext:workspaces
