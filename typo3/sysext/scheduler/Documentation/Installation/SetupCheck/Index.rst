.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _setup-check:

Checking the setup
^^^^^^^^^^^^^^^^^^

After installing the Scheduler, go to its BE module and call up the
"Setup check" screen which runs a couple of basic checks on your
installation. It will probably look something like this:

.. figure:: ../../Images/SetupCheck.png
   :alt: Setup check screen

   Checking the setup of the Scheduler

The first message shows a warning that the Scheduler has never run
yet or an information about the last run.

The second message should normally be okay. If there's an error
instead, it means that permissions to execute TYPO3's command-line
dispatcher must be checked (this is not strictly related to the
Scheduler).

The third message shows information about the current server time.
