.. include:: ../../Includes.txt

=========================================================================================
Important: #86785 - Calling scheduler command on CLI throws error if not in /var/www/html
=========================================================================================

See :issue:`86785`

Description
===========

Calling the scheduler command on CLI or in backend throws an error, if the TYPO3 CMS installation is not running in @/var/www/html/@.

After saving the scheduler task, the logger instance is serialized to the database with absolute paths (FileWriter::class).
Exclusion of the logger instance from the record during save to database prevents that error.

Impact
======

The stored and serialized tasks from upgraded TYPO3 instances and new saved tasks still work as before.
The logger instance is initialized while running the scheduler task and is not saved to the serialized task
object anymore. The logger (FileWriter::class) can open and write the log file to the current environment paths.

.. index:: Backend, ext:scheduler
