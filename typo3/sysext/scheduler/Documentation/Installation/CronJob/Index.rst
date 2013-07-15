.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _cron-job:

Setting up the cron job
^^^^^^^^^^^^^^^^^^^^^^^

Tasks registered with the Scheduler can be run manually from the BE
module. However this is of limited use. To really benefit from the
Scheduler, it must be set up on the server to run regularly. The
following chapters describe how to set this up on Unix or Unix-like
system (including Mac OS X) and on Windows.


.. _frequency:

Choosing a frequency
""""""""""""""""""""

Whatever system the Scheduler will run on, the first step is to define
the frequency at which it should run. The Scheduler script should set
up to run pretty often, but not unnecessarily often either. The
frequency should be that of the most often running task or some
frequency that fits all tasks.

For example, if you have some tasks running every quarter of an hour
and some others running every hour, it is useless to have the
Scheduler run every 5 minutes. On the other hand, if you have tasks
scheduled to run every 10 minutes and others every 15 minutes, you
will want to run the Scheduler every 5 minutes. Indeed, if you run it
only at 10-minute intervals, it will run – assuming it is 8 o'clock –
at 08:10, 08:20, 08:30, etc.. So the tasks that should run at 08:15
will actually run 5 minutes late.


.. _unix-mac:

On Unix and Mac OS X
""""""""""""""""""""

On such systems the Scheduler must be set up as a cron job. There are
several ways to achieve this, although the simplest is probably to add
it to some user's crontab. Edit that user's crontab using:

::

   crontab -e

and add a line like

::

   */15 * * * * /usr/local/bin/php /home/bob/www/typo3/cli_dispatch.phpsh scheduler

Save the modified crontab. Obviously the paths have to be adapted to
your system. The above command will call up the Scheduler every 15
minutes.

If you are editing system crontabs (for example :code:`/etc/crontab`
and :code:`/etc/cron.d/\*` ), there will be one additional parameter
to enter, i.e. the user with which the job should run. Example
(additional user parameter in bold):

::

   */15 * * * * www /usr/local/bin/php /home/bob/www/typo3/cli_dispatch.phpsh scheduler

This will run the job as user "www".

If you are not familiar with cron syntax, refer to some Unix
administration book or start with the Wikipedia page about it
(http://en.wikipedia.org/wiki/Cron).


.. _windows:

On Windows
""""""""""

On Windows, cron jobs are called "Scheduled tasks" and run with the
:code:`schtasks` utility. :code:`SchTasks.exe` performs operations
similar to those provided by Scheduled Tasks in the Control Panel. You
can use either tool to create, delete, configure, or simply display
scheduled tasks.

Assuming you want to run the TYPO3 Scheduler every 15 minutes, use the
following command line to create a new task:

::

   schtasks /create /sc minute /mo 15 /tn "T3scheduler" /tr "c:\winstaller\php\php.exe c:\winstaller\htdocs\quickstart\typo3\cli_dispatch.phpsh scheduler"

At task creation you will be prompted to give a password or you can
use the :code:`/u` and :code:`/p` switches to provide user and
password information. Note that the user must be a member of the
Administrators group on the computer where the command will run.

The full reference for :code:`schtasks` is available at:
http://technet.microsoft.com/en-us/library/bb490996.aspx

