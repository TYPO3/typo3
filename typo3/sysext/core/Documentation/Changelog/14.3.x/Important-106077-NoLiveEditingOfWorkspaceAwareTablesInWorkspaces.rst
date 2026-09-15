..  include:: /Includes.rst.txt

..  _important-106077-1789503919:

============================================================================
Important: #106077 - No live editing of workspace aware tables in workspaces
============================================================================

See :issue:`106077`

Description
===========

The TCA :php:`ctrl` option :php:`versioningWS_alwaysAllowLiveEdit` allows
editing records of a table in any workspace without creating workspace
versions. It is meant for tables without workspace support.

When combined with :php:`versioningWS`, records of a workspace aware table
were created and modified directly in live, although an editor worked in a
workspace. This combination is no longer effective: records of workspace aware
tables are always versioned in a workspace.

The option is automatically removed from tables with :php:`versioningWS`
enabled during TCA migration, which triggers a deprecation log entry. This also
applies to inline child tables that are made workspace aware automatically.

Extensions combining both options should remove
:php:`versioningWS_alwaysAllowLiveEdit` from their TCA.

..  index:: TCA, ext:core, ext:workspaces
