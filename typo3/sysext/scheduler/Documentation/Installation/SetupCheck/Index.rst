:navigation-title: Setup Check

..  include:: /Includes.rst.txt
..  _setup-check:

=============================================
Checking the setup of the scheduler extension
=============================================

The scheduler check provides useful information for setting up cronjobs.

..  figure:: /Images/SetupCheckButton.png
    :alt: The TYPO3 Backend module "Scheduler" with Button "Setup check" highlighted

    Click on the button :guilabel:`Setup check` to open the popup

..  figure:: /Images/SetupCheck.png
    :alt: The "Setup check" modal popup in module "Scheduler"

The first message shows when the scheduler was last run. If it was never run
there will be a warning displayed.

The second messages tells you which command (with absolute paths) must be
executed by the cron job.

The third message shows information about the current server time.
