..  include:: /Includes.rst.txt

..  _feature-106739-1747815655:

======================================================
Feature: #106739 - Scheduler Tasks as native TCA table
======================================================

See :issue:`106739`

Description
===========

For historical reasons the TYPO3 system extension "scheduler" had a special
handling for persisting to the database. This was done by serializing the whole
task object into a field of the database table `tx_scheduler_task`. In TYPO3 v14
this has been reworked in order to split the logic of custom fields into a
database field "parameters" of type `json` and a new database field `tasktype`
for the scheduler task name or the CLI command.

An upgrade wizard is in place for all existing scheduler tasks to use the new
database structure.


Impact
======

Due to this change, TCA is now introduced for this database table within TYPO3.
This adds several advantages over the previous solution:

* The editing interface is now handled via FormEngine, making it flexible and
  extensible.
* Any changes are now stored to the database via DataHandler, allowing to
  customize persistence changes – in addition, the history and audit
  functionality is available for scheduler tasks as well.
* Database entries of `tx_scheduler_task` can now be used via import/export
  functionality.
* Previously removed tasks can be restored via recycler.

Other functionality such as support for automated database restrictions and
TcaSchema are available as well.

..  index:: TCA, ext:scheduler
