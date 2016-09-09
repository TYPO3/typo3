
.. include:: ../../Includes.txt

========================================================
Feature: #35245 - Rework workspace notification settings
========================================================

See :issue:`35245`

Description
===========

The current notification settings have some drawbacks and are not easy to
understand if it comes the the expected behavior in the workspace module.
The settings are defined in each sys_workspace and sys_workspace_stage
record and are evaluated in the workspace module if sending a particular
element to be reviewed to the previous or next stage.

Currently there are the following notification settings:

* on stages

  * "edit stage": takes recipients from "adminusers" field
    (workspace owners)

  * "ready to publish" stage: takes recipients from "members" field
    (workspace members)

* on preselection of recipients

  * "all (non-strict)": if users from workspace setting (field "adminusers"
    or "members") are also in the specific "default_users" setting for the
    stage, the checkbox is enabled by default and cannot be changed,
    otherwise it's not checked

  * "all (strict)": all users from workspace setting (field "adminusers"
    or "members") are checked and cannot be changed

  * "some (strict)": all users from workspace setting (field "adminusers"
     or "members") are checked, but still can be changed

* behavior

  * sending to "edit" stage: members are notified per default

  * sending to "ready to publish" stage: owners are notified per default

The changes extends the possibilities to define notification settings:

* on stages

  * add settings for "publish-execute" stage (actual publishing process)

* on preselection of recipients

  * remove modes

  * replace settings for showing the dialog and whether modifying the
    preselection is allowed at all (getting rid of the "strict" modes)

  * add possibilities to defined notification recipients

    * owner & members as defined in the accordant fields

    * editors that have been working on a particular element

    * responsible persons (on custom stages only)

Impact
======

The meaning and behavior of the workspaces notification settings concerning
preselected recipients and the possibility to modify the selection on moving
an element to a particular change is different now. However, an upgrade wizard
helps to upgrade the settings to the new definitions.
