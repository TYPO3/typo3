.. include:: ../../Includes.txt

============================================================
Feature: #79263 - Scheduler CLI available as Symfony Command
============================================================

See :issue:`79263`

Description
===========

Calling the scheduler to process a task is now callable via CLI through `typo3/cli_dispatch.phpsh scheduler` and
`typo3/sysext/core/bin/typo3 scheduler:run`.

The following aliases for the scheduler options are now available:

* `--task=13` or `--task 13` as synonym to `-i 13` to run a specific task
* `--force` as synonym to `-f` to force to run a specific task in combination with `--task` or `-i` 
* `--stop` as synonym to `-s` to stop a specific task in combination with `--task` or `-i`

.. index:: CLI, ext:scheduler