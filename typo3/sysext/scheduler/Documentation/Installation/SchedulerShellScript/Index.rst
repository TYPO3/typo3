:navigation-title: Run the scheduler

..  include:: /Includes.rst.txt
..  _scheduler-shell-script:

==========================================
Running the scheduler: typo3 scheduler:run
==========================================

The scheduler provides a PHP shell script designed to be run using
TYPO3's command-line dispatcher. To try and run that script a first
time, type the following command.

..  tabs::

    ..  group-tab:: Composer-based installation

        ..  code-block:: bash

            vendor/bin/typo3 scheduler:run

    ..  group-tab:: Classic installation

        ..  code-block:: bash

            typo3/sysext/core/bin/typo3 scheduler:run

See also `TYPO3 Explained: Run a command from the command
line <https://docs.typo3.org/permalink/t3coreapi:symfony-console-commands-cli>`_.

Show help
=========

In order to show help:

..  tabs::

    ..  group-tab:: Composer-based installation

        ..  code-block:: bash

            vendor/bin/typo3 scheduler:run --help

    ..  group-tab:: Classic installation

        ..  code-block:: bash

            typo3/sysext/core/bin/typo3 scheduler:run --help

..  _scheduler-shell-script-options:

Providing options to the shell script
=====================================

The shell scripts accepts a number of options which can be provided in any
order.

..  _scheduler-shell-script-options-i:

`--task (-i)`
-------------

To run a specific scheduler task you need to provide the uid of the task:

..  tabs::

    ..  group-tab:: Composer-based installation

        ..  code-block:: bash

            # Run task with uid 42
            vendor/bin/typo3 scheduler:run --task=42

            # Run tasks with uid 3 and 14
            vendor/bin/typo3 scheduler:run --task=3 --task=14

    ..  group-tab:: Classic installation

        ..  code-block:: bash

            # Run task with uid 42
            typo3/sysext/core/bin/typo3 scheduler:run --task=42

            # Run tasks with uid 3 and 14
            typo3/sysext/core/bin/typo3 scheduler:run --task=3 --task=14

The tasks will be executed in the order in which the parameters are provided.

..  _scheduler-shell-script-options-f:

`-f`
----

To run a task even if it is disabled (or not scheduled to be run yet),
you need to provide the force option:

..  tabs::

    ..  group-tab:: Composer-based installation

        ..  code-block:: bash

            # Run task with uid 42, even if disabled
            vendor/bin/typo3 scheduler:run --task=42 -f

    ..  group-tab:: Classic installation

        ..  code-block:: bash

            # Run task with uid 42, even if disabled
            typo3/sysext/core/bin/typo3 scheduler:run --task=42 -f

This will also run the task with uid 42 if it is disabled.

..  _scheduler-shell-script-options-v:

`-v`
----

A single -v flag will output errors only. Two -vv flags will also output additional
information:

..  tabs::

    ..  group-tab:: Composer-based installation

        ..  code-block:: bash

            # Run task with uid 42, with detailed stack traces
            vendor/bin/typo3 scheduler:run --task=42 -vv

    ..  group-tab:: Classic installation

        ..  code-block:: bash

            # Run task with uid 42, with detailed stack traces
            typo3/sysext/core/bin/typo3 scheduler:run --task=42 -vv

