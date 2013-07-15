.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _scheduler-user:

The Scheduler user
^^^^^^^^^^^^^^^^^^

When tasks are launched manually from the BE module, they are executed
using the current BE user. When they are run from the command line,
however, there needs to be a specific user, called "\_cli\_scheduler".

This can be a simple BE user with no specific rights at first. However
some tasks may need to check some specific rights, for example if they
use TCEmain. It may thus be necessary, depending on the tasks that you
run, to give additional rights to the "cli\_scheduler" user.

