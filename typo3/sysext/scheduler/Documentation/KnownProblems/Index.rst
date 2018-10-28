.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _known-problems:

Known problems
--------------

The main problem currently is that a running task cannot be killed,
because no relation exists to the (cron) process that is running the
Scheduler. The process pid could be retrieved, but that may not work
on all platforms. And can the process be killed afterwards? Anyway it
may not be safe to do that.


