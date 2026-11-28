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

namespace TYPO3\CMS\Core\Schema;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Resolves the record type of a database row of a TCA table, which is the
 * value that selects the "types" section (the sub schema) of the record.
 *
 * The type is either defined by a field of the record itself (ctrl "type" => "CType"),
 * or by a field of a related record (ctrl "type" => "file:type"), where "file" is the
 * field pointing to the related record and "type" is the field of the related record.
 * In the latter case, the version of the related record in the current workspace is used.
 *
 * @internal not part of the public TYPO3 Core API
 */
#[Autoconfigure(public: true)]
final readonly class RecordTypeResolver
{
    public function __construct(
        private TcaSchemaFactory $schemaFactory,
        private ConnectionPool $connectionPool,
        private Context $context,
    ) {}

    /**
     * Returns the type value of the record, no matter if a sub schema exists for it,
     * or null if the table has no types or the value cannot be resolved.
     */
    public function getTypeValue(string $table, array $row): ?string
    {
        if (!$this->schemaFactory->has($table)) {
            return null;
        }
        $schema = $this->schemaFactory->get($table);
        if (!$schema->supportsSubSchema()) {
            return null;
        }
        $typeInformation = $schema->getSubSchemaTypeInformation();
        $typeValue = $typeInformation->isPointerToForeignFieldInForeignSchema()
            ? $this->getTypeValueFromForeignRecord($typeInformation, $row)
            : ($row[$typeInformation->getFieldName()] ?? null);
        // A type field with multiple values (e.g. renderType=selectCheckBox) only works with exactly one value
        if (is_array($typeValue)) {
            $typeValue = count($typeValue) === 1 ? reset($typeValue) : null;
        }
        if ($typeValue === null || is_array($typeValue)) {
            return null;
        }
        return (string)$typeValue;
    }

    /**
     * Returns the type of the record if the table defines a sub schema for it, null otherwise.
     */
    public function getRecordType(string $table, array $row): ?string
    {
        $typeValue = $this->getTypeValue($table, $row);
        if ($typeValue === null || !$this->schemaFactory->get($table)->hasSubSchema($typeValue)) {
            return null;
        }
        return $typeValue;
    }

    /**
     * Returns the type of the record if the table defines a sub schema for it. Otherwise,
     * the type "0" is returned, or "1" if the table does not define the type "0".
     */
    public function getRecordTypeWithFallback(string $table, array $row): string
    {
        $recordType = $this->getRecordType($table, $row);
        if ($recordType !== null) {
            return $recordType;
        }
        return $this->schemaFactory->has($table) && $this->schemaFactory->get($table)->hasSubSchema('0') ? '0' : '1';
    }

    private function getTypeValueFromForeignRecord(SchemaTypeInformation $typeInformation, array $row): ?string
    {
        $pointerField = $typeInformation->getFieldName();
        if (array_key_exists($pointerField, $row)) {
            $foreignUid = $row[$pointerField];
        } elseif (MathUtility::canBeInterpretedAsInteger($row['uid'] ?? null)) {
            $foreignUid = $this->getFieldValue($typeInformation->getSchemaName(), (int)$row['uid'], $pointerField);
        } else {
            return null;
        }
        if (!MathUtility::canBeInterpretedAsInteger($foreignUid) || (int)$foreignUid <= 0) {
            return null;
        }
        $foreignTable = (string)$typeInformation->getForeignSchemaName();
        $foreignTypeField = (string)$typeInformation->getForeignFieldName();
        if (!$this->schemaFactory->has($foreignTable) || !$this->schemaFactory->get($foreignTable)->hasField($foreignTypeField)) {
            return null;
        }
        if (!$this->schemaFactory->get($foreignTable)->isWorkspaceAware()) {
            return $this->getFieldValue($foreignTable, (int)$foreignUid, $foreignTypeField);
        }
        return $this->getFieldValueOfWorkspaceRecord($foreignTable, (int)$foreignUid, $foreignTypeField);
    }

    private function getFieldValue(string $table, int $uid, string $field): ?string
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $value = $queryBuilder
            ->select($field)
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
        return $value === false || $value === null ? null : (string)$value;
    }

    /**
     * Uses the version of the record in the current workspace, if one exists.
     * Records deleted in the current workspace, or only existing in another workspace, have no value.
     */
    private function getFieldValueOfWorkspaceRecord(string $table, int $uid, string $field): ?string
    {
        $workspaceId = (int)$this->context->getPropertyFromAspect('workspace', 'id', 0);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $rows = $queryBuilder
            ->select($field, 't3ver_state')
            ->from($table)
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                        $queryBuilder->expr()->in('t3ver_wsid', $queryBuilder->createNamedParameter(array_unique([0, $workspaceId]), Connection::PARAM_INT_ARRAY)),
                    ),
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                        $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter($workspaceId, Connection::PARAM_INT)),
                        $queryBuilder->expr()->gt('t3ver_wsid', 0),
                    ),
                )
            )
            ->orderBy('t3ver_oid', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();
        $record = $rows[0] ?? null;
        if ($record === null || VersionState::tryFrom((int)$record['t3ver_state']) === VersionState::DELETE_PLACEHOLDER) {
            return null;
        }
        return isset($record[$field]) ? (string)$record[$field] : null;
    }
}
