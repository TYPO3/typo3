..  include:: /Includes.rst.txt

..  _important-106532-1744207039:

========================================================================
Important: #106532 - Changed database storage format for Scheduler Tasks
========================================================================

See :issue:`106532`

Description
===========

TYPO3's system extension scheduler has stored its tasks to be executed with PHP-serialized
storage format in the database since its inception.

This has led to many problems, e.g. when changing a class property to be fully typed,
or when a class name has changed to use PHP 5 namespaces back then, or when
renaming a class or a class property.

This has now changed, where as the task object now stores the "tasktype" (typically
the class name) and its options in a "parameters" as JSON-encoded value as well as
the execution details (DB field execution_details) in separate fields of the database
table :sql:`tx_scheduler_task`. This way, the task object can be re-constituted with a
properly defined API, avoiding pains in the future.

All existing tasks are compatible with the new internal format. An upgrade wizard
ensures that the previously serialized objects are now transferred into the new
format. If this wizard does not disappear after being executed, it means there
are tasks that failed to be migrated and may need manual inspection or re-creation.
Inspect all tasks of :sql:`tx_scheduler_task` where the "tasktype" column is empty.
The old serialized data format is somewhat human-readable (or can be inspected with PHP
deserializers), so re-creating a task with its former configuration options should be
possible.

Please note that this upgrade step needs to be performed in the context of TYPO3 v14;
performing the wizard in future TYPO3 versions may not succeed due to changes in the Task objects.

..  index:: Database, ext:scheduler
