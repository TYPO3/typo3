..  include:: /Includes.rst.txt

..  _feature-106739-1747815655:

======================================================
Feature: #106739 - Scheduler tasks as native TCA table
======================================================

See :issue:`106739`

Description
===========

For historical reasons, the TYPO3 system extension
:composer:`typo3/cms-scheduler` used a special mechanism for persisting data to
the database. This was achieved by serializing the entire task object into a
single field of the database table :sql:`tx_scheduler_task`.

In TYPO3 v14, this behavior has been reworked. The logic for custom fields has
been split into a dedicated database field :sql:`parameters` of type :sql:`json`
and a new database field :sql:`tasktype` that stores the scheduler task name or
CLI command.

An upgrade wizard automatically migrates all existing scheduler tasks to the new
database structure.

Impact
======

With this change, TCA is now defined for the :sql:`tx_scheduler_task` table in
TYPO3. This provides several advantages over the previous implementation:

*    The editing interface is now handled via FormEngine, making it more flexible
     and extensible.
*    Changes are stored to the database via DataHandler, which allows
     customization of persistence operations. The history and audit
     functionality is now also available for scheduler tasks.
*    Database entries of :sql:`tx_scheduler_task` can now be exported and
     imported using the standard import/export functionality.
*    Deleted tasks can be restored via the recycler module.

Additional functionality such as support for automated database restrictions and
the TCA schema is available as well.

..  index:: TCA, ext:scheduler
