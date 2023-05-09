<?php

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

namespace TYPO3\CMS\Core\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DataHandler hook class to check the integrity of submitted be_groups data
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
class BackendUserGroupIntegrityCheck
{
    public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, DataHandler $dataHandler): void
    {
        if ($table !== 'be_groups') {
            return;
        }
        $backendUserGroup = BackendUtility::getRecord($table, $id, 'explicit_allowdeny');
        $explicitAllowDenyFields = GeneralUtility::trimExplode(',', $backendUserGroup['explicit_allowdeny'] ?? '');
        foreach ($explicitAllowDenyFields as $value) {
            if ($value !== '' && str_starts_with($value, 'tt_content:list_type:')) {
                if (!in_array('tt_content:CType:list', $explicitAllowDenyFields, true)) {
                    $dataHandler->log(
                        $table,
                        $id,
                        SystemLogDatabaseAction::UPDATE,
                        0,
                        SystemLogErrorClassification::WARNING,
                        'Editing of at least one plugin was enabled but editing the page content type "Insert Plugin" is still disallowed. Group members won\'t be able to edit plugins unless you activate editing for the content type.',
                    );
                }
                return;
            }
        }
    }
}
