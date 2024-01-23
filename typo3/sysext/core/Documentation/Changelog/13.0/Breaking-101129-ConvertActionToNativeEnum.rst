.. include:: /Includes.rst.txt

.. _breaking-Action-1687355374:

========================================================
Breaking: #101129 - Convert Action to native backed enum
========================================================

See :issue:`101129`

Description
===========

The class :php:`\TYPO3\CMS\Scheduler\Task\Enumeration\Action` is now
converted to a native backed enum. In addition the class is moved to
the namespace :php:`\TYPO3\CMS\Scheduler` and renamed to
:php:`SchedulerManagementAction`.

Impact
======

Since :php:`\TYPO3\CMS\Scheduler\Task\Enumeration\Action` is no longer
a class, the existing class constants are no longer available.
In addition it's not possible to instantiate it anymore.

Affected installations
======================

Third-party extensions using the following class constants:

- :php:`\TYPO3\CMS\Scheduler\Task\Enumeration\Action::ADD`
- :php:`\TYPO3\CMS\Scheduler\Task\Enumeration\Action::EDIT`
- :php:`\TYPO3\CMS\Scheduler\Task\Enumeration\Action::LIST`

Class instantiation:

- :php:`new Action('a-string')`


Migration
=========

Include the enum :php:`SchedulerManagementAction` from namespace :php:`\TYPO3\CMS\Scheduler`
as a replacement for :php:`Action`.

Use the new syntax

- :php:`\TYPO3\CMS\Scheduler\SchedulerManagementAction::ADD`
- :php:`\TYPO3\CMS\Scheduler\SchedulerManagementAction::EDIT`
- :php:`\TYPO3\CMS\Scheduler\SchedulerManagementAction::LIST`

as well as the :php:`tryFrom($aString)` static method of the backed enum.


.. index:: Backend, NotScanned, ext:linkvalidator, ext:recycler, ext:reports, ext:scheduler
