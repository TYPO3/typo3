..  include:: /Includes.rst.txt

..  _breaking-107323-1755864247:

======================================================
Breaking: #107323 - Workspace "Freeze Editing" removed
======================================================

See :issue:`107323`

Description
===========

The workspace record flag *Freeze Editing* has been removed without
replacement, effectively removing this feature.

The functionality had only been partially implemented and exhibited several
usability and conceptual issues. It was therefore decided to remove it entirely
in favor of more reliable and flexible workspace configuration options.

Impact
======

"Freezing" a workspace to prevent editing of records is no longer possible.

Affected installations
======================

Since *Freeze Editing* did not provide any visible feedback to editors, it was
likely used only rarely.

During the database compare, the field `freeze` will be removed from the
table :sql:`sys_workspace`, effectively "unfreezing" all previously frozen
workspaces.

Migration
=========

A more robust alternative to *Freeze Editing* is to configure a **custom
workspace stage** and assign **responsible persons**.
In combination with the following workspace options:

- `Publish only content in publish stage`
- `Restrict publishing to workspace owners`

you can create a workflow in which only designated members or groups are
permitted to move records into the *Ready to publish* stage and/or perform
publishing actions.

While other members can still edit records, such edits will automatically reset
the workspace stage back to *Editing*, restarting the review cycle for those
changes.

..  index:: Backend, NotScanned, ext:workspaces
