.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _scheduler-user:

The Scheduler user
^^^^^^^^^^^^^^^^^^

When tasks are launched manually from the BE module, they are executed
using the current BE user. When tasks are run from the command line,
however, a specific command-line user, called "\_cli\_" with admin rights
is created on-the-fly.