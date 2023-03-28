.. include:: /Includes.rst.txt

.. _feature-100143-1678575248:

==================================================================
Feature: #100143 - Add scheduler command to execute and list tasks
==================================================================

See :issue:`100143`

Description
===========

The CLI command :bash:`scheduler:run` of EXT:scheduler offers a way to run a
task using a cronjob. It also allows to run tasks if the UID of the task
is known.

To make it more convenient to use the command, :bash:`scheduler:list` and
:bash:`scheduler:execute` were introduced.

The :bash:`scheduler:list` command shows an overview of all available tasks or
a given group with an option to watch and reload the list every X seconds
(default every 1 second).

Example:

..  code-block:: bash

    # List all tasks in group 1 and group 2 and watch for changes every second.
    vendor/bin/typo3 scheduler:list --group 1 --group 2 --watch

    # List all tasks without a group and watch for changes every 2 seconds.
    vendor/bin/typo3 scheduler:list --group 0 --watch 2

    # Same as above with shortcut parameter
    vendor/bin/typo3 scheduler:list -g 0 -w 2


The :bash:`scheduler:execute` command displays a list of groups and available
tasks for the selection. If a group is selected all tasks within this group are
executed.

Example:

..  code-block:: bash

    # Run alls tasks without a group and task 8
    vendor/bin/typo3 scheduler:execute --task g:0 --task 8

    # Same as above with shortcut parameter
    vendor/bin/typo3 scheduler:execute -t g:0 -t 8

Impact
======

The new commands :bash:`scheduler:list` and :bash:`scheduler:execute` enable
the user to manage and run tasks without leaving the terminal.

.. index:: Backend, ext:scheduler
