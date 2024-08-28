.. include:: /Includes.rst.txt

.. _feature-104655-1724859386:

========================================================================
Feature: #104655 - Add console command to mark upgrade wizards as undone
========================================================================

See :issue:`104655`

Description
===========

A new CLI command :bash:`typo3 upgrade:mark:undone` has been
introduced. It allows to mark a previously executed upgrade wizard as "undone",
so it can be run again.

This makes the existing functionality from the install tool also available on
CLI.

..  note::

    Bear in mind that wizards theoretically can cause data inconsistencies when
    being run again. Also, a wizard may not run properly again when its
    pre-requisites no longer apply after its first run.

Impact
======

You can now mark an already executed upgrade wizard as "undone" with
:bash:`typo3 upgrade:markUndone <wizardIdentifier>`

.. index:: CLI, ext:install
