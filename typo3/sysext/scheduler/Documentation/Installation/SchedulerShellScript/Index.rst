.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _scheduler-shell-script:

The Scheduler shell script
^^^^^^^^^^^^^^^^^^^^^^^^^^

The Scheduler provides a PHP shell script designed to be run using
TYPO3's command-line dispatcher. To try and run that script a first
time, type the following command (adapting the path to PHP and TYPO3
to match your system):

::

   /usr/local/bin/php /home/bob/www/typo3/cli_dispatch.phpsh scheduler

If the "cli\_scheduler" user was not created, this will result in the
following error:

::

   ERROR: No backend user named "_cli_scheduler" was found! [Database: my_typo3_database]

If the user exists, you should see nothing, as the Scheduler doesn't
give any visual feedback while running.

.. _options:

Providing options to the shell script
"""""""""""""""""""""""""""""""""""""

The shell scripts accepts a number of options which can be provided in any
order.

To run a specific scheduler task you need to provide the uid of the task:

::

   /usr/local/bin/php /home/bob/www/typo3/cli_dispatch.phpsh scheduler -i 8

The example will trigger the task with uid 8.

To run a task even if it is disabled, you need to provide the force option

::

   /usr/local/bin/php /home/bob/www/typo3/cli_dispatch.phpsh scheduler -i 8 -f

This will also run the task with uid 8 if it is disabled.
