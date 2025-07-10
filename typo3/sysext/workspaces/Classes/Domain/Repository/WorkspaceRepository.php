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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Workspaces\Domain\Model\Workspace;

/**
 * @internal
 */
readonly class WorkspaceRepository
{
    public function findByUid(int $uid): Workspace
    {
        $record = BackendUtility::getRecord('sys_workspace', $uid);
        if ($record === null) {
            throw new \RuntimeException('Record "sys_workspace:' . $uid . '" not found', 1752135508);
        }
        return new Workspace(
            uid: (int)$record['uid'],
            title: (string)$record['title'],
            owners: (string)$record['adminusers'],
            members: (string)$record['members'],
            isEditStageDialogEnabled: (bool)((int)$record['edit_allow_notificaton_settings'] & 0x1),
            isEditStagePreselectionChangeable: (bool)((int)$record['edit_allow_notificaton_settings'] & 0x2),
            areEditStageOwnersPreselected: (bool)((int)$record['edit_notification_preselection'] & 0x1),
            areEditStageMembersPreselected: (bool)((int)$record['edit_notification_preselection'] & 0x2),
            editStageDefaultRecipients: (string)$record['edit_notification_defaults'],
            isPublishStageDialogEnabled: (bool)((int)$record['publish_allow_notificaton_settings'] & 0x1),
            isPublishStagePreselectionChangeable: (bool)((int)$record['publish_allow_notificaton_settings'] & 0x2),
            arePublishStageOwnersPreselected: (bool)((int)$record['publish_notification_preselection'] & 0x1),
            arePublishStageMembersPreselected: (bool)((int)$record['publish_notification_preselection'] & 0x2),
            publishStageDefaultRecipients: (string)$record['publish_notification_defaults'],
            isExecuteStageDialogEnabled: (bool)((int)$record['execute_allow_notificaton_settings'] & 0x1),
            isExecuteStagePreselectionChangeable: (bool)((int)$record['execute_allow_notificaton_settings'] & 0x2),
            areExecuteStageOwnersPreselected: (bool)((int)$record['execute_notification_preselection'] & 0x1),
            areExecuteStageMembersPreselected: (bool)((int)$record['execute_notification_preselection'] & 0x2),
            executeStageDefaultRecipients: (string)$record['execute_notification_defaults'],
        );
    }
}
