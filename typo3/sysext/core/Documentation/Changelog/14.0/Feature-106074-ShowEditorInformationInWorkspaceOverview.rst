..  include:: /Includes.rst.txt

..  _feature-106074-1762860569:

========================================================================
Feature: #106074 - Show editor information in workspace "Publish" module
========================================================================

See :issue:`106074`

Description
===========

The "Last changed" column in the :guilabel:`Content > Publish` module has
been improved. It now displays the username and avatar of the editor who most
recently modified the corresponding record.

..  note::
    The "Content > Publish" was called "Web > Workspaces" before TYPO3 v14, see also
    `Feature: #107628 - Improved backend module naming and structure <https://docs.typo3.org/permalink/changelog:feature-107628-1729026000>`_.

The column shows the editor's username in a badge, or `Unknown` if no editor
information is available. This helps reviewers quickly identify who last worked
on each workspace record.

Impact
======

Workspace information now reveals the last editor of a record within the
workspace context.

..  index:: Backend, ext:workspaces
