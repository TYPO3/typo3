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
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
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
        private readonly TcaSchemaFactory $tcaSchemaFactory,
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
        $schema = $this->tcaSchemaFactory->get($tableName);
        if ($schema->hasCapability(TcaSchemaCapability::UpdatedAt)) {
            $fieldValues[$schema->getCapability(TcaSchemaCapability::UpdatedAt)->getFieldName()] = $this->context->getAspect('date')->get('timestamp');
        }
        if ($schema->hasCapability(TcaSchemaCapability::CreatedAt)) {
            $fieldValues[$schema->getCapability(TcaSchemaCapability::CreatedAt)->getFieldName()] = $this->context->getAspect('date')->get('timestamp');
        }

        // Generate UUIDs for UUID columns that are not nullable
        $uuidColumns = $schema->getFields(
            static fn(FieldTypeInterface $field): bool => $field->isType(TableColumnType::UUID)
        )->getNames();
        if ($uuidColumns !== []) {
            $values = $this->recordData->generate($tableName, $fieldValues);
            foreach ($uuidColumns as $uuidColumn) {
                $fieldValues[$uuidColumn] = $values[$uuidColumn];
            }
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
