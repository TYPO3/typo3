:navigation-title: Table Garbage Collection

..  include:: /Includes.rst.txt

..  _table-garbage-collection-task:

=============================
Table garbage collection task
=============================

The table garbage collection task can take a more elaborate
configuration which is detailed below.

..  contents:: Table of contents

..  _table-garbage-collection-task-usage:

Using the garbage collection task
=================================

The task can be registered to clean up a particular table, in which
case you simply choose the table and the minimum age of the records to
delete from the task configuration screen.

..  figure:: /Images/TableGarbageCollectionTaskConfiguration.png
    :alt: Table Garbage Collection task configuration

    Configuring the table garbage collection task

In case no minimum age is choosen, the configured :php:`expirePeriod` is used.

..  figure:: /Images/TableGarbageCollectionTaskConfiguration-2.png
    :alt: Table Garbage Collection task configuration default expire period

    Configuring the table garbage collection task with default expire period

It is also possible to clean up all configured table by
checking the "Clean all available tables" box.

The configuration for
the tables to clean up is stored in the TCA of table `tx_scheduler_task`, in
field `tables`.

This configuration is an array with the table names as fields and the following
entries:

-   option :php:`expireField` can be used to point to a table field
    containing an expiry timestamp. This timestamp will then be used to
    decide whether a record has expired or not. If its timestamp is in the
    past, the record will be deleted.

-   if a table has no expiry field, one can use a combination of a date
    field and an expiry period to decide which records should be deleted.
    The corresponding options are :php:`dateField` and :php:`expirePeriod`.
    The expiry period is expressed in days.

..  _table-garbage-collection-task-example:

Example: Configure additional tables for the "Garbage Collection" task
======================================================================

..  deprecated:: 14.0
    The previous configuration method using
    :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']`
    has been deprecated and will be removed in TYPO3 v15.

    See also: `Changelog Deprecation: #107550 - Table Garbage Collection Task configuration via $GLOBALS <https://docs.typo3.org/permalink/changelog:deprecation-107550-1736193200>`_

..  literalinclude:: _codesnippets/_tx_scheduler_garbage_collection.php.inc
    :language: php
    :caption: packages/my_extension/Configuration/TCA/Overrides/tx_scheduler_garbage_collection.php

..  include:: /_Includes/_ExtendingSchedulerTca.rst.txt

The first part of the configuration indicates that records older than
180 days should be removed from table :code:`tx_myextension_my_table` ,
based on the timestamp field called "tstamp". The second part
indicates that old records should be removed from table
:code:`tx_myextension_my_other_table` directly based on the field `expire`
which contains expiration dates for each record.


..  _table-garbage-collection-task-migration:

Migration: Supporting custom tables for garbage collection for both TYPO3 13 and 14
===================================================================================

If your extension supports both TYPO3 13 (or below) and 14 keep the registration
of additional tables in the extensions :file:`ext_localconf.php` until support
for TYPO3 13 is removed:

..  literalinclude:: _codesnippets/_additional.php.inc
    :language: php
    :caption: packages/my_extension/ext_localconf.php
