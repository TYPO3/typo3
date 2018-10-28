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
time, type the following command (adapting the path to TYPO3
to match your system and add the full path to the php interpreter
in front of the command if php is not on the search path):

::

   /home/user/www/typo3/sysext/core/bin/typo3 scheduler:run
   /usr/local/bin/php /home/user/www/typo3/sysext/core/bin/typo3 scheduler:run

If your installation is a composer based instance you can use this command line
in your installation directory:

::

   bin/typo3 scheduler:run
   /usr/local/bin/php bin/typo3 scheduler:run


.. _options:

Providing options to the shell script
"""""""""""""""""""""""""""""""""""""

The shell scripts accepts a number of options which can be provided in any
order.

To run a specific scheduler task you need to provide the uid of the task:

::

   /home/user/www/typo3/sysext/core/bin/typo3 scheduler:run --task=8

The example will trigger the task with uid 8.

To run a task even if it is disabled, you need to provide the force option

::

   /home/user/www/typo3/sysext/core/bin/typo3 scheduler:run --task=8 -f

This will also run the task with uid 8 if it is disabled.
