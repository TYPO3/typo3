.. include:: ../../Includes.txt


.. _scheduler:

Scheduler tasks
^^^^^^^^^^^^^^^

The "workspaces" extension provides two Scheduler tasks.

Workspaces auto-publication
  This task checks if any workspace has a scheduled publishing date.
  If yes and if that date is passed, then all changes that have reached
  the "Ready to publish" stage are published to the Live workspace.

Workspaces cleanup preview links
  When :ref:`preview links <users-guide-preview-link>` are generated,
  they are stored in the database (in table "sys_preview"). This task
  will delete any link which has expired.
