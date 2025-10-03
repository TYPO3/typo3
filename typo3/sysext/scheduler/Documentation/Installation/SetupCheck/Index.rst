:navigation-title: Setup Check

..  include:: /Includes.rst.txt
..  _setup-check:

=============================================
Checking the setup of the scheduler extension
=============================================

After installing the Scheduler, go to its BE module and call up the
"Setup check" screen which runs a couple of basic checks on your
installation. It will probably look something like this:

..  figure:: /Images/SetupCheck.png
    :alt: Screenshot of the Scheduler backend module, option "Scheduler setup check" chosen

    Checking the setup of the Scheduler

The first message shows when the scheduler was last run. If it was never run
there will be a warning displayed.

The second messages tells you which command (with absolute paths) must be
executed by the cron job.

The third message shows information about the current server time.
