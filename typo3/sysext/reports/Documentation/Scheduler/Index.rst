.. include:: /Includes.rst.txt

.. _scheduler-task:

==============
Scheduler task
==============

If the system extension :ref:`scheduler is installed <ext_scheduler:installation>`,
you can create automatic reports with the help of a scheduler task.

To create a task for the reports functionality go to
:guilabel:`System > Scheduler`, click on :guilabel:`+ (add Task)` and chose
:guilabel:`System Status Update (reports)` as :guilabel:`Class`.

Enter :guilabel:`Notification Email Addresses` where the reports should be sent
and chose whether you want to be informed with each run.

The remaining settings are standard task settings provided by the scheduler
extension.

.. figure:: /Images/SchedulerTask.png
   :class: with-shadow

   Create a :guilabel:`System Status Update` task in :guilabel:`System > Scheduler`

System status notification mail
===============================

You will receive mails looking like this:

.. code-block:: text
    :caption: Example mail from the System status notification

    This report contains all System Status Notifications from your TYPO3
    installation. Please check the status report for more information.

    Site: [DDEV] TYPO3

    Issues:
    [WARN] System environment check                 - 1 Test(s)
    ### Trusted hosts pattern is insecure: 1
