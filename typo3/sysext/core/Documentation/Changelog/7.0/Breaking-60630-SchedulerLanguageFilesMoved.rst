
.. include:: ../../Includes.txt

=================================================
Breaking: #60630 - Scheduler Language Files Moved
=================================================

See :issue:`60630`

Description
===========

The language files of the scheduler extension are moved to EXT:scheduler/Resources/Private/Language/


Impact
======

Labels are not translated when being fetched from old file location.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses a language file from EXT:scheduler
or if localization overrides of these files are registered. Those overridden labels may not
work anymore.


Migration
=========

Use new path to language file instead or create/copy the labels to an own language file and
adapt existing overrides to the new file locations.


.. index:: Backend, ext:scheduler