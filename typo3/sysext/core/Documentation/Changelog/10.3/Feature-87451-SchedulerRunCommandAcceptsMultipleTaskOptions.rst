.. include:: /Includes.rst.txt

=====================================================================
Feature: #87451 - scheduler:run command accepts multiple task options
=====================================================================

See :issue:`87451`

Description
===========

The `scheduler:run` command now accepts multiple `--task` options.

The tasks will be executed in the order in which they are given:

.. code-block:: bash

   ./typo3/sysext/core/bin/typo3 scheduler:run --task 1 --task 2


It is now also possible to pass verbose flags to the command to get more information about what is
going on.

A single `-v` flag will output errors only. Two `-vv` flags will also output additional information.

Impact
======

The new feature allows the execution of tasks in a given order.

This can be used to debug side effects between tasks that are executed within the same scheduler run.

.. index:: CLI, ext:scheduler
