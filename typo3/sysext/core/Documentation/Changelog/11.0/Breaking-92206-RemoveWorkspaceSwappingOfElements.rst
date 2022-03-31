.. include:: /Includes.rst.txt

========================================================
Breaking: #92206 - Remove workspace swapping of elements
========================================================

See :issue:`92206`

Description
===========

When using workspaces, putting modified content into the live workspace can be achieved by two methods:

1. Publishing
Content is replaced with the live version, and the current live version is removed.

2. Swapping
Content is switched (swapped) with the live version, making the current live version the previously versioned content.

Especially when doing

* partial swapping
* multiple swapping
* swapping newly created content

TYPO3 will leave the workspace in an inconsistent state.

The swapping mechanism was therefore removed, leaving "Publishing" the only option to select for editors to push content from a workspace into the live website.


Impact
======

The database field :sql:`sys_workspace.swap_modes` and the TCA option :php:`sys_workspace.swap_modes` are removed.

The Workspace module only shows the "Publish" option, as "Swap" is removed.

The auto-publishing feature now always publishes instead of optionally swaps content.


Affected Installations
======================

TYPO3 installations which use workspaces with the swapping option
activated.


Migration
=========

All draft content is using the publishing mechanism, whereas there
is no migration needed.

.. index:: Database, TCA, NotScanned, ext:workspaces
