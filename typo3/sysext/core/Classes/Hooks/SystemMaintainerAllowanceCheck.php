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

namespace TYPO3\CMS\Core\Hooks;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;

/**
 * DataHandler hook to ensure that only system maintainers can change details of system maintainers.
 *
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
final class SystemMaintainerAllowanceCheck
{
    public function processDatamap_postProcessFieldArray(string $status, string $table, int|string $id, array &$fieldArray, DataHandler $dataHandler): void
    {
        if ($table !== 'be_users' || $status !== 'update' || empty($fieldArray)) {
            return;
        }
        // Do not allow a non system maintainer admin to change details of system maintainers.
        $systemMaintainers = array_map(intval(...), $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? []);
        // False if current user is not in system maintainer list or if switch to user mode is active
        $isCurrentUserSystemMaintainer = $dataHandler->BE_USER->isSystemMaintainer();
        $isTargetUserInSystemMaintainerList = in_array((int)$id, $systemMaintainers, true);
        if (!$isCurrentUserSystemMaintainer && $isTargetUserInSystemMaintainerList) {
            $fieldArray = [];
            $dataHandler->log(
                $table,
                (int)$id,
                SystemLogDatabaseAction::UPDATE,
                0,
                SystemLogErrorClassification::SECURITY_NOTICE,
                'Only system maintainers can change details of other system maintainers. The values have not been updated.'
            );
        }
    }
}
