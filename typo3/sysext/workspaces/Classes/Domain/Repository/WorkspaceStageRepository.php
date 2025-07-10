<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Workspaces\Domain\Repository;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Workspaces\Authorization\WorkspacePublishGate;
use TYPO3\CMS\Workspaces\Domain\Model\Workspace;
use TYPO3\CMS\Workspaces\Domain\Model\WorkspaceStage;
use TYPO3\CMS\Workspaces\Service\StagesService;

/**
 * @internal
 */
readonly class WorkspaceStageRepository
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private StagesService $stagesService,
        private WorkspacePublishGate $workspacePublishGate,
    ) {}

    /**
     * @return WorkspaceStage[]
     */
    public function findAllStagesByWorkspace(BackendUserAuthentication $backendUser, Workspace $workspaceRecord): array
    {
        $stages = [];

        // Add 'internal' edit stage
        $stages[] = new WorkspaceStage(
            uid: StagesService::STAGE_EDIT_ID,
            isEditStage: true,
            isExecuteStage: false,
            title: $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_editing'),
            isDialogEnabled: $workspaceRecord->isEditStageDialogEnabled,
            isPreselectionChangeable: $workspaceRecord->isEditStagePreselectionChangeable,
            defaultComment: '',
            isAllowed: true,
            allRecipients: $this->stagesService->resolveBackendUserIds(
                $workspaceRecord->editStageDefaultRecipients
                . ',' . $workspaceRecord->owners
                . ',' . $workspaceRecord->members
            ),
            preselectedRecipients: $this->stagesService->resolveBackendUserIds(
                $workspaceRecord->editStageDefaultRecipients
                . ($workspaceRecord->areEditStageOwnersPreselected ? ',' . $workspaceRecord->owners : '')
                . ($workspaceRecord->areEditStageMembersPreselected ? ',' . $workspaceRecord->members : '')
            )
        );

        // Add custom stages
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_workspace_stage');
        $result = $queryBuilder
            ->select('*')
            ->from('sys_workspace_stage')
            ->where($queryBuilder->expr()->eq('parentid', $queryBuilder->createNamedParameter($workspaceRecord->uid, Connection::PARAM_INT)))
            ->orderBy('sorting')
            ->executeQuery();
        while ($record = $result->fetchAssociative()) {
            $stages[] = new WorkspaceStage(
                uid: (int)$record['uid'],
                isEditStage: false,
                isExecuteStage: false,
                title: (string)$record['title'],
                isDialogEnabled: (bool)((int)$record['allow_notificaton_settings'] & 1),
                isPreselectionChangeable: (bool)((int)$record['allow_notificaton_settings'] & 2),
                defaultComment: (string)$record['default_mailcomment'],
                // @todo: avoid / tune or inline a simplified version of that method to reduce DB calls.
                isAllowed: $backendUser->workspaceCheckStageForCurrent((int)$record['uid']),
                allRecipients: $this->stagesService->resolveBackendUserIds(
                    $record['notification_defaults']
                    . ',' . $workspaceRecord->owners
                    . ',' . $workspaceRecord->members
                    . ',' . $record['responsible_persons']
                ),
                preselectedRecipients: $this->stagesService->resolveBackendUserIds(
                    $record['notification_defaults']
                    . (((int)$record['notification_preselection'] & 0x1) ? ',' . $workspaceRecord->owners : '')
                    . (((int)$record['notification_preselection'] & 0x2) ? ',' . $workspaceRecord->members : '')
                    . (((int)$record['notification_preselection'] & 0x8) ? ',' . $record['responsible_persons'] : '')
                )
            );
        }

        // Add 'internal' publish stage
        $stages[] = new WorkspaceStage(
            uid: StagesService::STAGE_PUBLISH_ID,
            isEditStage: false,
            isExecuteStage: false,
            title: $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_ready_to_publish'),
            isDialogEnabled: $workspaceRecord->isPublishStageDialogEnabled,
            isPreselectionChangeable: $workspaceRecord->isPublishStagePreselectionChangeable,
            defaultComment: '',
            // @todo: avoid / tune or inline a simplified version of that method to reduce DB calls.
            isAllowed: $backendUser->workspaceCheckStageForCurrent(StagesService::STAGE_PUBLISH_ID),
            allRecipients: $this->stagesService->resolveBackendUserIds(
                $workspaceRecord->publishStageDefaultRecipients
                . ',' . $workspaceRecord->owners
                . ',' . $workspaceRecord->members
            ),
            preselectedRecipients: $this->stagesService->resolveBackendUserIds(
                $workspaceRecord->publishStageDefaultRecipients
                . ($workspaceRecord->arePublishStageOwnersPreselected ? ',' . $workspaceRecord->owners : '')
                . ($workspaceRecord->arePublishStageMembersPreselected ? ',' . $workspaceRecord->members : '')
            )
        );

        // Add 'internal' 'pseudo' execute stage
        $stages[] = new WorkspaceStage(
            uid: StagesService::STAGE_PUBLISH_EXECUTE_ID,
            isEditStage: false,
            isExecuteStage: true,
            title: $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_publish'),
            isDialogEnabled: $workspaceRecord->isExecuteStageDialogEnabled,
            isPreselectionChangeable: $workspaceRecord->isExecuteStagePreselectionChangeable,
            defaultComment: '',
            isAllowed: $this->workspacePublishGate->isGranted($backendUser, $workspaceRecord->uid),
            allRecipients: $this->stagesService->resolveBackendUserIds(
                $workspaceRecord->executeStageDefaultRecipients
                . ',' . $workspaceRecord->owners
                . ',' . $workspaceRecord->members
            ),
            preselectedRecipients: $this->stagesService->resolveBackendUserIds(
                $workspaceRecord->executeStageDefaultRecipients
                . ($workspaceRecord->areExecuteStageOwnersPreselected ? ',' . $workspaceRecord->owners : '')
                . ($workspaceRecord->areExecuteStageMembersPreselected ? ',' . $workspaceRecord->members : '')
            )
        );

        return $stages;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
