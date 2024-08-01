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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandler;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordData;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;
use TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandlerInterface;

/**
 * General table handler
 *
 * @internal
 */
final class General extends AbstractTableHandler implements TableHandlerInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly RecordData $recordData,
        private readonly RecordFinder $recordFinder,
        private readonly Context $context,
    ) {}

    /**
     * Match always
     */
    public function match(string $tableName): bool
    {
        return true;
    }

    /**
     * Adds rows
     */
    public function handle(string $tableName): void
    {
        // First insert an empty row and get the uid of this row since
        // some fields need this uid for relations later.
        $fieldValues = [
            'pid' => $this->recordFinder->findPidOfMainTableRecord($tableName),
        ];
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['tstamp'])) {
            $fieldValues[$GLOBALS['TCA'][$tableName]['ctrl']['tstamp']] = $this->context->getAspect('date')->get('timestamp');
        }
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['crdate'])) {
            $fieldValues[$GLOBALS['TCA'][$tableName]['ctrl']['crdate']] = $this->context->getAspect('date')->get('timestamp');
        }
        $connection = $this->connectionPool->getConnectionForTable($tableName);
        $connection->insert($tableName, $fieldValues);
        $fieldValues['uid'] = $connection->lastInsertId();
        $fieldValues = $this->recordData->generate($tableName, $fieldValues);
        // Do not update primary identifier uid anymore, db's choke on that for good reason
        $updateValues = $fieldValues;
        unset($updateValues['uid']);
        $connection->update(
            $tableName,
            $updateValues,
            ['uid' => $fieldValues['uid']]
        );

        $this->generateTranslatedRecords($tableName, $fieldValues);
    }
}
