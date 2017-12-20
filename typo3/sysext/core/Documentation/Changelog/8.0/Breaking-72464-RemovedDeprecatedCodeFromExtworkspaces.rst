
.. include:: ../../Includes.txt

==============================================================
Breaking: #72464 - Removed deprecated code from EXT:workspaces
==============================================================

See :issue:`72464`

Description
===========

The following components have been changed during the development of TYPO3 CMS 7
and lead to deprecated code which is removed in TYPO3 CMS 8.

* remove rewritten toolbar item (#62800)
* remove rewritten notification parts (#35245)


Impact
======

Using or extending `\TYPO3\CMS\Workspaces\ExtDirect\ToolbarMenu` will fail since
it has been removed.

Using \TYPO3\CMS\Workspaces\Service\StagesService::getNotificationMode($stageId)
will fail.

Relying on the following database fields in the tables sys_workspace and
sys_workspace_stage will fail:
* sys_workspace.edit_notification_mode
* sys_workspace.publish_notification_mode
* sys_workspace.execute_notification_mode
* sys_workspace_stage.notification_mode


Affected Installations
======================

All installations using workspaces and notifications that have not been migrated
to TYPO3 CMS 7, yet.


Migration
=========

First migrate to TYPO3 CMS 7 and use the accordant upgrade wizard
(WorkspacesNotificationSettingsUpdate) and then upgrade to TYPO3 CMS 8.

.. index:: PHP-API, Backend, ext:workspaces
