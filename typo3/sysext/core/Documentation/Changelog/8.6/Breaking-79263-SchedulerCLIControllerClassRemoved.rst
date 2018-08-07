.. include:: ../../Includes.txt

=========================================================
Breaking: #79263 - Scheduler CLI Controller class removed
=========================================================

See :issue:`79263`

Description
===========

The PHP class :php:`TYPO3\CMS\Scheduler\Controller\SchedulerCliController` has been removed from the system extension "scheduler"
due to the migration to a native Symfony Command.


Impact
======

Instantiating the mentioned PHP class will result in a fatal PHP error.


Affected Installations
======================

Any installation with a custom extension using this PHP class directly.

Please note that this does not affect any calls via CLI to trigger the scheduler via `typo3/cli_dispatch.phpsh scheduler` directly. This
still works as before.


Migration
=========

Remove any direct calls to the PHP class and use the provided APIs via CLI instead.

.. index:: CLI, ext:scheduler
