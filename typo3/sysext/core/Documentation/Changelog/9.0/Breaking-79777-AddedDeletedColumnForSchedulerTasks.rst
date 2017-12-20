.. include:: ../../Includes.txt

=================================================================
Breaking: #79777 - EXT:scheduler - Deleted column for tasks added
=================================================================

See :issue:`79777`

Description
===========

Instead of deleting the record directly in the database, a a "deleted" column was added for ``tx_scheduler_task``
in a way to have developers / admins revive a task later-on.


Impact
======

It is not possible to remove a task completely using the delete button in the scheduler module.

Instead the "deleted" column will be set to 1 and the task won't show up in the backend module, and cannot
be called via CLI anymore.


Affected Installations
======================

If an extension is accessing the database table ``tx_scheduler_task`` directly, an additional `deleted=0` check
needs to be added.


.. index:: Backend, CLI, NotScanned, ext:scheduler
