.. include:: /Includes.rst.txt

.. _feature-111149-1792768126:

======================================================================
Feature: #111149 - Remember the chosen workspace preview mode per user
======================================================================

See :issue:`111149`

Description
===========

The workspace preview in the backend offers three modes to compare the
workspace version of a page with its live version: a slider between both
versions ("slider"), and the two versions stacked vertically ("vbox") or
side by side ("hbox").

The mode a backend user selects is now stored in the backend user settings
under `workspaces.previewMode` and used as the initial mode when the
workspace preview is opened again. Previously, the preview always started
with the first mode of the page TSconfig option
:typoscript:`workspaces.splitPreviewModes`.

If the stored mode is not allowed for the page by
:typoscript:`workspaces.splitPreviewModes`, the first allowed mode
is used as before.

Impact
======

Backend users who prefer to compare the versions side by side or stacked no
longer need to switch the preview mode each time they open the workspace
preview.

.. index:: Backend, ext:workspaces
