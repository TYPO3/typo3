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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\Fixtures;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;

/**
 * Logs an error at the end of every nested DataHandler run, so tests can see
 * which errors of nested runs reach the error log of the outer instance.
 */
final class NestedRunErrorHook
{
    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        if (!$dataHandler->isOuterMostInstance()) {
            $dataHandler->log('', 0, SystemLogDatabaseAction::CHECK, null, SystemLogErrorClassification::USER_ERROR, 'Error of a nested datamap run');
        }
    }

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        if (!$dataHandler->isOuterMostInstance()) {
            $dataHandler->log('', 0, SystemLogDatabaseAction::CHECK, null, SystemLogErrorClassification::USER_ERROR, 'Error of a nested cmdmap run');
        }
    }
}
