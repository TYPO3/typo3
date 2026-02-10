..  include:: /Includes.rst.txt

..  _important-108433-1770744353:

===================================================================================
Important: #108433 - Workspace selector moved to sidebar with color and description
===================================================================================

See :issue:`108433`

Description
===========

The workspace selector has been moved from the top toolbar to the backend
sidebar. The selector now displays the full name of the currently active
workspace and provides a dropdown to switch between available workspaces.

A colored top bar indicator is shown whenever a workspace is active, giving
editors a clear visual cue about which workspace they are working in.

Color per workspace
-------------------

Administrators can now assign a color to each workspace via the
:guilabel:`Color` field in the workspace record. The selected color is used
for the top bar indicator and the workspace selector in the sidebar, making
it easier to visually distinguish workspaces at a glance.

The following colors are available: red, orange, yellow, lime, green, teal,
blue, indigo, purple, and magenta. The default color for new workspaces is
orange.

Description as tooltip
----------------------

The :guilabel:`Description` field of a workspace record is now displayed as
a tooltip on both the workspace selector dropdown items and the top bar
indicator. This allows administrators to provide additional context about
the purpose of a workspace, which editors can see by hovering over the
workspace name.

Impact
======

Editors will see the workspace selector in the sidebar instead of the top
toolbar. The workspace indicator bar at the top of the backend now reflects
the configured color of the active workspace.

Administrators are encouraged to assign meaningful colors and descriptions to
their workspaces to improve the editing experience for their teams.

..  index:: Backend, ext:workspaces
