..  include:: /Includes.rst.txt

..  _feature-106074-1762860569:

================================================================
Feature: #106074 - Show editor information in workspace overview
================================================================

See :issue:`106074`

Description
===========

The "Last changed" column of the listing in the :guilabel:`Content > Workspace`
module has been improved. It now also displays the username and avatar of
the editor, who modified corresponding record most recently.

The column shows the editor's username in a badge, or "Unknown"
if no editor information is available. This helps reviewers
quickly identify who last worked on each workspace record.

Impact
======

Workspace information now reveals the last editor of a record
within the workspace context.

..  index:: Backend, ext:workspaces
