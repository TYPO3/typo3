.. include:: ../../Includes.txt

===================================================================
Deprecation: #86001 - Workspaces tasks migrated to symfony commands
===================================================================

See :issue:`86001`

Description
===========

The custom scheduler tasks for workspace publishing and removing of preview links have been migrated
to custom symfony commands, making the functionality specifically within the scheduler context obsolete.

The following tasks should not be used anymore:

* Workspaces auto-publication
* Workspaces cleanup preview links

The following related classes have been marked as deprecated:

* :php:`TYPO3\CMS\Workspaces\Service\AutoPublishService`
* :php:`TYPO3\CMS\Workspaces\Task\AutoPublishTask`
* :php:`TYPO3\CMS\Workspaces\Task\CleanupPreviewLinkTask`

The scheduler tasks are still available, but marked as obsolete.


Impact
======

Executing one of the tasks above will trigger a PHP :php:`E_USER_DEPRECATED` error. Calling any of the classes from the outside
will also trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 Installations using workspaces in combination with any of the two scheduler tasks.


Migration
=========

Create a new scheduler task based on the Symfony Command and select one of the symfony-based commands
"cleanup:previewlinks" or "workspace:auto-publish" respectively.

.. index:: CLI, FullyScanned, ext:workspaces
