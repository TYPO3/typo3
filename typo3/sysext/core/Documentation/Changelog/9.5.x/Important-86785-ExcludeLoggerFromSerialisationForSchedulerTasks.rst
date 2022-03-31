.. include:: /Includes.rst.txt

================================================================================
Important: #86785 - Exclude logger from serialisation on save for scheduler task
================================================================================

See :issue:`86785`

Description
===========

Calling the scheduler command on CLI or in backend throws an error, when you
created a scheduler task in one environment (for example one where TYPO3 was
running in `/var/www/html`) - and then you take that database and move to another
environment (where TYPO3 is running somewhere else, for example in
`/var/www/something`). Trying to execute any task in the scheduler resulted
in an error, because the Logger instance that was stored with the task in the
database had the wrong log file path.

To get rid of that problem, the logger is no longer stored with the task in the
database, but instead re-instantiated when the task is run
(which means it checks for correct paths on the current environment).

Exclusion of the logger instance from the record during save to database
prevents the error.

Impact
======

The stored and serialized tasks from upgraded TYPO3 instances and new saved tasks still work as before.
The logger instance is initialized while running the scheduler task and is not saved to the serialized task
object anymore. The logger (FileWriter::class) can open and write the log file to the current environment paths.

.. index:: Backend, ext:scheduler
