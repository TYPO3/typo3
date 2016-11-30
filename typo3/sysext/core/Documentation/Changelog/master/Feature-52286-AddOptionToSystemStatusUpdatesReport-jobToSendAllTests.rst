.. include:: ../../Includes.txt

====================================================================================
Feature: #52286 - Add option to "system status updates" report-job to send all tests
====================================================================================

See :issue:`52286`

Description
===========

Sometimes it could be useful to get every test in the "System Status Updates (reports)" - also via mail.

A checkbox was added to the job-configuration for the decision to get a mail if the
system has WARNING or ERROR events, or just get everything.
If the checkbox is not set (default) it works like before, including WARNING and ERROR events only.


Impact
======

If the checkbox `Notification for all type of status, not only warning and error` is checked,
then the `System Status Update (reports)` contains all type of notifications.

.. index:: Backend