.. include:: /Includes.rst.txt



.. _scheduler-shell-script:

==========================
The scheduler shell script
==========================

The scheduler provides a PHP shell script designed to be run using
TYPO3's command-line dispatcher. To try and run that script a first
time, type the following command.

On a Composer based system::

   bin/typo3 scheduler:run


On a system without Composer (adapt the path to TYPO3
to match your system)::

   typo3/sysext/core/bin/typo3 scheduler:run

You might have to add the full path to the PHP interpreter
in front of the command if PHP is not on the search path::

   /usr/local/bin/php typo3/sysext/core/bin/typo3 scheduler:run


In the following examples, we will use the path to `typo3` for systems
with Composer.

Show help
=========

In order to show help::

   bin/typo3 scheduler:run --help


.. _scheduler-shell-script-options:

Providing options to the shell script
=====================================

The shell scripts accepts a number of options which can be provided in any
order.

`--task (-i)`
-------------

To run a specific scheduler task you need to provide the uid of the task::

   bin/typo3 scheduler:run --task=8

or

   bin/typo3 scheduler:run -i 8


The example will trigger the task with uid 8.

.. versionadded:: 10.3

    It is possible to run several tasks::

        bin/typo3 scheduler:run --task=8 --task=2

    The tasks will be executed in the order in which they are provided.

`--f`
-----

To run a task even if it is disabled (or not scheduled to be run yet),
you need to provide the force option::

   bin/typo3 scheduler:run --task=8 -f

This will also run the task with uid 8 if it is disabled.

`--v`
-----

A single -v flag will output errors only. Two -vv flags will also output additional
information::

    bin/typo3 scheduler:run --task=8 -v
