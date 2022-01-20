<?php

declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandler;

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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordData;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;
use TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandlerInterface;

/**
 * General table handler
 */
class General extends AbstractTableHandler implements TableHandlerInterface
{
    /**
     * Match always
     *
     * @param string $tableName
     * @return bool
     */
    public function match(string $tableName): bool
    {
        return true;
    }

    /**
     * Adds rows
     *
     * @param string $tableName
     */
    public function handle(string $tableName): void
    {
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        $recordData = GeneralUtility::makeInstance(RecordData::class);
        $context = GeneralUtility::makeInstance(Context::class);

        // First insert an empty row and get the uid of this row since
        // some fields need this uid for relations later.
        $fieldValues = [
            'pid' => $recordFinder->findPidOfMainTableRecord($tableName),
        ];
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['tstamp'])) {
            $fieldValues[$GLOBALS['TCA'][$tableName]['ctrl']['tstamp']] = $context->getAspect('date')->get('timestamp');
        }
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['crdate'])) {
            $fieldValues[$GLOBALS['TCA'][$tableName]['ctrl']['crdate']] = $context->getAspect('date')->get('timestamp');
        }
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['cruser_id'])) {
            $fieldValues[$GLOBALS['TCA'][$tableName]['ctrl']['cruser_id']] = $context->getAspect('backend.user')->get('id');
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
        $connection->insert($tableName, $fieldValues);
        $fieldValues['uid'] = $connection->lastInsertId($tableName);
        $fieldValues = $recordData->generate($tableName, $fieldValues);
        // Do not update primary identifier uid anymore, db's choke on that for good reason
        $updateValues = $fieldValues;
        unset($updateValues['uid']);
        $connection->update(
            $tableName,
            $updateValues,
            [ 'uid' => $fieldValues['uid'] ]
        );

        $this->generateTranslatedRecords($tableName, $fieldValues);
    }
}
