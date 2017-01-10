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


.. _options:

Providing options to the shell script
"""""""""""""""""""""""""""""""""""""

The shell scripts accepts a number of options which can be provided in any
order.

To run a specific scheduler task you need to provide the uid of the task:

::

   /usr/local/bin/php /home/bob/www/typo3/cli_dispatch.phpsh scheduler --task=8

The example will trigger the task with uid 8.

To run a task even if it is disabled, you need to provide the force option

::

   /usr/local/bin/php /home/bob/www/typo3/cli_dispatch.phpsh scheduler --task=8 -f

This will also run the task with uid 8 if it is disabled.
