
.. include:: /Includes.rst.txt

======================================================
Feature: #68746 - Add annotation for CLI only commands
======================================================

See :issue:`68746`

Description
===========

The PHPDoc annotation `@cli` was added to indicate Extbase CommandController
commands to be usable on CLI only.
In general each defined CommandController can be selected within the Extbase
CommandController Task in the scheduler.
For some commands like `extbase:help:help` running in a scheduler task is not
wanted or needed. Now those commands can be excluded from the scheduler command selection.


Impact
======

Extbase `CommandController` commands annotated with `@cli` are not shown as
command in the scheduler task.


.. index:: CLI, ext:extbase
