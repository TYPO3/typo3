..  include:: /Includes.rst.txt

..  _breaking-107323-1755864247:

======================================================
Breaking: #107323 - Workspace "Freeze Editing" removed
======================================================

See :issue:`107323`

Description
===========

The workspace record flag "Freeze Editing" has been removed without
direct substitution, removing this feature.

That feature had so far only been very incompletely implemented and had so
many usability flaws that it was a better solution to remove it entirely in
favor of other features.


Impact
======

"Freezing" a workspace to prevent editing records is no longer possible.


Affected installations
======================

Since "Freeze Editing" gave no feedback to editors whatsoever, it has probably
been used very seldomly. The database analyzer will remove the database field
"freeze" from table "sys_workspace", "unfreezing" all "frozen" workspaces.


Migration
=========

A better substitution than "freezing" a workspace is to set up a "Custom stage",
and set some "Responsible persons". In combination with flags
"Publish only content in publish stage" and "Restrict publishing to workspace owners"
a setup can be created where only certain workspaces members (or groups) are allowed
to push records into "Ready for publish" stage and/or publish. Editing by other members
is not disallowed as such, but it will reset the workspace stage back to "Editing",
which will restart the review loop for such records.


..  index:: Backend, NotScanned, ext:workspaces
