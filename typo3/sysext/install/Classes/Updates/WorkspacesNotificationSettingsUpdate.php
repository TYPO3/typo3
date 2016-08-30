<?php
namespace TYPO3\CMS\Install\Updates;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Migrate the workspaces notification settings to the enhanced schema.
 */
class WorkspacesNotificationSettingsUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Migrate the workspaces notification settings to the enhanced schema';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')) {
            return false;
        }

        if ($this->isWizardDone()) {
            return false;
        }

        $workspacesCount = $this->getDatabaseConnection()->exec_SELECTcountRows(
            'uid',
            'sys_workspace',
            'deleted=0'
        );

        $stagesCount = $this->getDatabaseConnection()->exec_SELECTcountRows(
            'uid',
            'sys_workspace_stage',
            'deleted=0'
        );

        if ($workspacesCount + $stagesCount > 0) {
            $description = 'The workspaces notification settings have been extended'
                . ' and need to be migrated to the new definitions. This update wizard'
                . ' upgrades the accordant settings in the availble workspaces and stages.';
            return true;
        } else {
            $this->markWizardAsDone();
        }

        return false;
    }

    /**
     * Perform the database updates for workspace records
     *
     * @param array &$databaseQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessages)
    {
        $databaseConnection = $this->getDatabaseConnection();

        $workspaceRecords = $databaseConnection->exec_SELECTgetRows('*', 'sys_workspace', 'deleted=0');
        foreach ($workspaceRecords as $workspaceRecord) {
            $update = $this->prepareWorkspaceUpdate($workspaceRecord);
            if ($update !== null) {
                $databaseConnection->exec_UPDATEquery('sys_workspace', 'uid=' . (int)$workspaceRecord['uid'], $update);
                $databaseQueries[] = $databaseConnection->debug_lastBuiltQuery;
            }
        }

        $stageRecords = $databaseConnection->exec_SELECTgetRows('*', 'sys_workspace_stage', 'deleted=0');
        foreach ($stageRecords as $stageRecord) {
            $update = $this->prepareStageUpdate($stageRecord);
            if ($update !== null) {
                $databaseConnection->exec_UPDATEquery('sys_workspace_stage', 'uid=' . (int)$stageRecord['uid'], $update);
                $databaseQueries[] = $databaseConnection->debug_lastBuiltQuery;
            }
        }

        $this->markWizardAsDone();
        return true;
    }

    /**
     * Prepares SQL updates for workspace records.
     *
     * @param array $workspaceRecord
     * @return array|NULL
     */
    protected function prepareWorkspaceUpdate(array $workspaceRecord)
    {
        if (empty($workspaceRecord['uid'])) {
            return null;
        }

        $update = [];
        $update = $this->mapSettings($workspaceRecord, $update, 'edit', 'edit');
        $update = $this->mapSettings($workspaceRecord, $update, 'publish', 'publish');
        $update = $this->mapSettings($workspaceRecord, $update, 'publish', 'execute');
        return $update;
    }

    /**
     * Prepares SQL update for stage records.
     *
     * @param array $stageRecord
     * @return array|null
     */
    protected function prepareStageUpdate(array $stageRecord)
    {
        if (empty($stageRecord['uid'])) {
            return null;
        }

        $update = [];
        $update = $this->mapSettings($stageRecord, $update);
        return $update;
    }

    /**
     * Maps settings to new meaning.
     *
     * @param array $record
     * @param array $update
     * @param string $from
     * @param string $to
     * @return array
     */
    protected function mapSettings(array $record, array $update, $from = '', $to = '')
    {
        $fromPrefix = ($from ? $from . '_' : '');
        $toPrefix = ($to ? $to . '_' : '');

        $settings = 0;
        // Previous setting: "Allow notification settings during stage change"
        if ($record[$fromPrefix . 'allow_notificaton_settings']) {
            $settings += 1;
        }
        // Previous setting: "All are selected per default (can be changed)"
        if ((int)$record[$fromPrefix . 'notification_mode'] === 0) {
            $settings += 2;
        }

        // Custom stages: preselect responsible persons (8)
        if (isset($record['responsible_persons'])) {
            $preselection = 8;
        // Workspace "edit" stage: preselect members (2)
        } elseif ($to === 'edit') {
            $preselection = 2;
        // Workspace "publish" stage: preselect owners (1)
        } elseif ($to === 'publish') {
            $preselection = 1;
        // Workspace "execute" stage: preselect owners (1) and members (2) as default
        } else {
            $preselection = 1 + 2;
        }

        $update[$toPrefix . 'allow_notificaton_settings'] = $settings;
        $update[$toPrefix . 'notification_preselection'] = $preselection;

        return $update;
    }
}
