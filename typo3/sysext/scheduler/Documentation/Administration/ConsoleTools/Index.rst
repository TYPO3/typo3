:navigation-title: Console tools

..  include:: /Includes.rst.txt
..  _console-tools:

=======================================
Console tools to manage scheduler tasks
=======================================

Console commands to manage scheduler tasks include :command:`typo3 scheduler:list`,
:command:`typo3 scheduler:execute` and :command:`typo3 scheduler:run`.

You can display detailed help on these commands, by using the `--help` parameter to
display the help:

..  tabs::

    ..  group-tab:: Composer mode

        ..  code-block:: bash

            vendor/bin/typo3 scheduler:list --help

    ..  group-tab:: Classic mode

        ..  code-block:: bash

            typo3/sysext/core/bin/typo3 scheduler:list --help

See also: `Command usage in terminal environments <https://docs.typo3.org/permalink/t3coreapi:how-to-run-a-command>`_.

..  contents:: Table of contents

..  toctree::
    :glob:
    :caption: Subpages
    :titlesonly:

    *

..  _console-run:

Running the scheduler
=====================

The command :command:`typo3 scheduler:run` is usually called by the
`cron job <https://docs.typo3.org/permalink/typo3/cms-scheduler:cron-job>`_.

It looks for tasks that are **due**, and runs them. You can
optionally target specific task IDs, force them even if not due, or stop them.

..  tabs::

    ..  group-tab:: Composer mode

        ..  code-block:: bash

            vendor/bin/typo3 scheduler:run

    ..  group-tab:: Classic mode

        ..  code-block:: bash

            typo3/sysext/core/bin/typo3 scheduler:run

..  seealso::
    `Running the scheduler: typo3 scheduler:run <https://docs.typo3.org/permalink/typo3/cms-scheduler:scheduler-shell-script>`_

..  _console-execute:

Executing scheduler tasks
=========================

The command :command:`typo3 scheduler:execute` is a "manual fire" runner. You pick
tasks (IDs or whole groups) and it **executes them on demand**, regardless of
whether they are due. It can also prompt you interactively to choose.

..  tabs::

    ..  group-tab:: Composer mode

        ..  code-block:: bash

            # Note the id of the task
            vendor/bin/typo3 scheduler:list

            vendor/bin/typo3 scheduler:execute --task=<taskUid>

    ..  group-tab:: Classic mode

        ..  code-block:: bash

            # Find the id of the task
            typo3/sysext/core/bin/typo3 scheduler:list

            typo3/sysext/core/bin/typo3 scheduler:execute --task=<taskUid>

..  _console-list:

Listing all scheduler tasks
===========================

Command :command:`typo3 scheduler:list` can be used to list all available tasks.
This command basically displays the same information as the backend module
:guilabel:`Administration > Scheduler`.

..  tabs::

    ..  group-tab:: Composer mode

        ..  code-block:: bash

            vendor/bin/typo3 scheduler:list

    ..  group-tab:: Classic mode

        ..  code-block:: bash

            typo3/sysext/core/bin/typo3 scheduler:list
