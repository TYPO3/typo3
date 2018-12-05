.. include:: ../../Includes.txt

=================================================================================================
Breaking: #87081 - Language update (scheduler) task doesn't work after upgrading to TYPO3 >= v9.2
=================================================================================================

See :issue:`87081`

Description
===========

The language update command was moved away from ext:lang and was rewritten as a Symfony Console Commmand.
https://docs.typo3.org/typo3cms/extensions/core/latest/Changelog/9.2/Breaking-84131-RemovedClassesOfLanguageExtension.html


Impact
======

Running or editing this task is not possible anymore.


Affected Installations
======================

An installation is affected if a language update scheduler task was created and exists before an upgrade to TYPO3 >= 9.2.


Migration
=========

Delete all existing language update tasks within the scheduler or remove the corresponding record in the database directly.
Create a new scheduler task with selected class "Execute console commands" and select schedulable command "language:update".

.. index:: Backend, CLI, Frontend, NotScanned, ext:lang
