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

namespace TYPO3\CMS\Scheduler\Form\FieldInformation;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

/**
 * Renders "expiresPeriod" information for the selected table, which is used if nothing is specified for this field manually.
 *
 * @internal This is a specific scheduler implementation and is not considered part of the Public TYPO3 API.
 */
class ExpirePeriodInformation extends AbstractNode
{
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();

        if ($this->data['command'] !== 'edit'
            || $this->data['tableName'] !== 'tx_scheduler_task'
            || (int)($this->data['parameterArray']['itemFormElValue'] ?? 0) > 0
        ) {
            return $resultArray;
        }

        $refField = (string)($this->data['renderData']['fieldInformationOptions']['refField'] ?? '');
        if (($this->data['databaseRow'][$refField] ?? false) === false) {
            return $resultArray;
        }

        $selectedTable = (string)(is_array($this->data['databaseRow'][$refField]) ? $this->data['databaseRow'][$refField][0] : $this->data['databaseRow'][$refField]);
        $tableConfiguration = GeneralUtility::makeInstance(TableGarbageCollectionTask::class)->getTableConfiguration()[$selectedTable] ?? [];
        if (!isset($tableConfiguration['expirePeriod'])) {
            return $resultArray;
        }

        $resultArray['html'] = '
            <div class="badge badge-info mb-2">
                ' . sprintf($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.defaultExpirePeriod'), (int)$tableConfiguration['expirePeriod'], $selectedTable) . '
            </div>';

        return $resultArray;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
