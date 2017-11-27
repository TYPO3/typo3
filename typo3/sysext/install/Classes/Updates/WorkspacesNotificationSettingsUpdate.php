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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        if (!ExtensionManagementUtility::isLoaded('workspaces') || $this->isWizardDone()) {
            return false;
        }

        $workspacesCount = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_workspace')
            ->count(
                'uid',
                'sys_workspace',
                ['deleted' => 0]
            );
        $stagesCount = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_workspace_stage')
            ->count(
                'uid',
                'sys_workspace_stage',
                ['deleted' => 0]
            );
        if ($workspacesCount + $stagesCount > 0) {
            $description = 'The workspaces notification settings have been extended'
                . ' and need to be migrated to the new definitions. This update wizard'
                . ' upgrades the accordant settings in the available workspaces and stages.';
            return true;
        }
        $this->markWizardAsDone();

        return false;
    }

    /**
     * Perform the database updates for workspace records
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $workspaceConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_workspace');
        $queryBuilder = $workspaceConnection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $statement = $queryBuilder->select('*')->from('sys_workspace')->execute();
        while ($workspaceRecord = $statement->fetch()) {
            $update = $this->prepareWorkspaceUpdate($workspaceRecord);
            if ($update !== null) {
                $queryBuilder = $workspaceConnection->createQueryBuilder();
                $queryBuilder->update('sys_workspace')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($workspaceRecord['uid'], \PDO::PARAM_INT)
                        )
                    );
                foreach ($update as $field => $value) {
                    $queryBuilder->set($field, $value);
                }
                $databaseQueries[] = $queryBuilder->getSQL();
                $queryBuilder->execute();
            }
        }

        $stageConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_workspace_stage');
        $queryBuilder = $stageConnection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $statement = $queryBuilder->select('*')->from('sys_workspace_stage')->execute();
        while ($stageRecord = $statement->fetch()) {
            $update = $this->prepareStageUpdate($stageRecord);
            if ($update !== null) {
                $queryBuilder = $workspaceConnection->createQueryBuilder();
                $queryBuilder->update('sys_workspace_stage')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($stageRecord['uid'], \PDO::PARAM_INT)
                        )
                    );
                foreach ($update as $field => $value) {
                    $queryBuilder->set($field, $value);
                }
                $databaseQueries[] = $queryBuilder->getSQL();
                $queryBuilder->execute();
            }
        }

        $this->markWizardAsDone();
        return true;
    }

    /**
     * Prepares SQL updates for workspace records.
     *
     * @param array $workspaceRecord
     * @return array|null
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

        if (isset($record['responsible_persons'])) {
            // Custom stages: preselect responsible persons (8)
            $preselection = 8;
        } elseif ($to === 'edit') {
            // Workspace "edit" stage: preselect members (2)
            $preselection = 2;
        } elseif ($to === 'publish') {
            // Workspace "publish" stage: preselect owners (1)
            $preselection = 1;
        } else {
            // Workspace "execute" stage: preselect owners (1) and members (2) as default
            $preselection = 1 + 2;
        }

        $update[$toPrefix . 'allow_notificaton_settings'] = $settings;
        $update[$toPrefix . 'notification_preselection'] = $preselection;

        return $update;
    }
}
