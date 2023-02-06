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

namespace TYPO3\CMS\Core\Database\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Compares two Schemas and returns an instance of SchemaDiff.
 *
 * @internal not part of public core API.
 */
class Comparator extends \Doctrine\DBAL\Schema\Comparator
{
    protected AbstractPlatform $databasePlatform;

    public function __construct(protected AbstractPlatform $platform)
    {
        $this->databasePlatform = $platform;
        parent::__construct($platform);
    }

    public function compareSchemas(Schema $oldSchema, Schema $newSchema): SchemaDiff
    {
        return SchemaDiff::ensure(parent::compareSchemas($oldSchema, $newSchema));
    }

    /**
     * Returns the difference between the tables $fromTable and $toTable.
     *
     * If there are no differences this method returns the boolean false.
     */
    public function compareTables(Table $oldTable, Table $newTable): TableDiff
    {
        $newTableOptions = array_merge($oldTable->getOptions(), $newTable->getOptions());
        $optionDiff = ArrayUtility::arrayDiffAssocRecursive($newTableOptions, $oldTable->getOptions());
        $tableDifferences = parent::compareTables($oldTable, $newTable);
        // Rebuild TableDiff with enhanced TYPO3 TableDiff class
        $tableDifferences = TableDiff::ensure($tableDifferences);
        // Set the table options to be parsed in the AlterTable event. Only add changed table options.
        if (count($optionDiff) > 0) {
            $tableDifferences->setTableOptions($optionDiff);
        }
        return $tableDifferences;
    }

    /**
     * @todo: Remove this override after blocking issues has been resolved in the methods
     *        Comparator::typo3DiffColumn() and Comparator::doctrineDbalMajorThreeDiffColumn().
     *
     * @see Comparator::typo3DiffColumn()                       blocker, see comments
     * @see Comparator::doctrineDbalMajorThreeDiffColumn()      blocker, see comments
     */
    protected function columnsEqual(Column $column1, Column $column2): bool
    {
        // doctrine/dbal 4+ enforces the use of a platform and dispatches the column equal check to
        // the used platform. That's a change in behaviour for TYPO3, therefore we reintroduce the
        // old fallback without a set platform, the dropped `diffColumn()` methods.
        //
        // To avoid cloning the full `compareTables()` method code, we now override this transaction
        // method, not dispatching to the Platform->columnsEqual() and using the preserved `diffColumn()`
        // code chain.
        //
        // return parent::columnsEqual($column1, $column2);
        return $this->typo3DiffColumn($column1, $column2) === [];
    }

    /**
     * Returns the difference between the columns $column1 and $column2
     * by first checking the doctrine diffColumn. Extend the Doctrine
     * method by taking into account MySQL TINY/MEDIUM/LONG type variants.
     *
     * @todo    Move the column length override for MariaDB/MySQL to the extended platform classes, when the
     *          the workaround here can be removed and `AbstractPlatform::columnsEqual() used. Currently blocked
     *          by the used Comparator::doctrineDbalMajorThreeDiffColumn() code which compares differently than
     *          the new platform code of doctrine. See the todo regarding the platform options in that method.
     *
     * @see Comparator::doctrineDbalMajorThreeDiffColumn()      blocker
     */
    public function typo3DiffColumn(Column $column1, Column $column2): array
    {
        $changedProperties = $this->doctrineDbalMajorThreeDiffColumn($column1, $column2);
        // Only MySQL has variable length versions of TEXT/BLOB
        if (!($this->platform instanceof DoctrineMariaDBPlatform || $this->databasePlatform instanceof DoctrineMySQLPlatform)) {
            return $changedProperties;
        }
        $properties1 = $column1->toArray();
        $properties2 = $column2->toArray();
        if ($properties1['type'] instanceof BlobType || $properties1['type'] instanceof TextType) {
            // Doctrine does not provide a length for LONGTEXT/LONGBLOB columns
            $length1 = $properties1['length'] ?: 2147483647;
            $length2 = $properties2['length'] ?: 2147483647;

            if ($length1 !== $length2) {
                $changedProperties[] = 'length';
            }
        }
        return array_unique($changedProperties);
    }

    /**
     * Returns the difference between the columns
     *
     * If there are differences this method returns the changed properties as a
     * string array, otherwise an empty array gets returned.
     *
     * @deprecated Use {@see columnsEqual()} instead.
     *
     * @return string[]
     *
     * Note: cloned from doctrine/dbal 3.7.1
     */
    public function doctrineDbalMajorThreeDiffColumn(Column $column1, Column $column2): array
    {
        $properties1 = $column1->toArray();
        $properties2 = $column2->toArray();

        $changedProperties = [];

        if (get_class($properties1['type']) !== get_class($properties2['type'])) {
            $changedProperties[] = 'type';
        }

        foreach (['notnull', 'unsigned', 'autoincrement'] as $property) {
            if ($properties1[$property] === $properties2[$property]) {
                continue;
            }

            $changedProperties[] = $property;
        }

        // Null values need to be checked additionally as they tell whether to create or drop a default value.
        // null != 0, null != false, null != '' etc. This affects platform's table alteration SQL generation.
        if (
            ($properties1['default'] === null) !== ($properties2['default'] === null)
            || $properties1['default'] != $properties2['default']
        ) {
            $changedProperties[] = 'default';
        }

        if (
            ($properties1['type'] instanceof StringType && !$properties1['type'] instanceof GuidType) ||
            $properties1['type'] instanceof BinaryType
        ) {
            // check if value of length is set at all, default value assumed otherwise.
            $length1 = $properties1['length'] ?? 255;
            $length2 = $properties2['length'] ?? 255;
            if ($length1 !== $length2) {
                $changedProperties[] = 'length';
            }

            if ($properties1['fixed'] !== $properties2['fixed']) {
                $changedProperties[] = 'fixed';
            }
        } elseif ($properties1['type'] instanceof DecimalType) {
            if (($properties1['precision'] ?? 10) !== ($properties2['precision'] ?? 10)) {
                $changedProperties[] = 'precision';
            }

            if ($properties1['scale'] !== $properties2['scale']) {
                $changedProperties[] = 'scale';
            }
        }

        // A null value and an empty string are actually equal for a comment so they should not trigger a change.
        if (
            $properties1['comment'] !== $properties2['comment'] &&
            !($properties1['comment'] === null && $properties2['comment'] === '') &&
            !($properties2['comment'] === null && $properties1['comment'] === '')
        ) {
            $changedProperties[] = 'comment';
        }

        $platformOptions1 = $column1->getPlatformOptions();
        $platformOptions2 = $column2->getPlatformOptions();

        // NOTE:    This is an important point, as only the overlapping platform option keys are compared. Doctrine DBAL
        //          4.x comparison using the `columnsEqual()` method generate the create table statement type string for
        //          the column and comparing these strings. That means, that we cannot replace this old compare with the
        //          new one. At least this needs additional work and mainly for all extended platforms.
        // @todo    Invest time to evaluate this in Doctrine DBAL directly and getting a fix for it (3.x + 4.x)
        foreach (array_keys(array_intersect_key($platformOptions1, $platformOptions2)) as $key) {
            if ($properties1[$key] === $properties2[$key]) {
                continue;
            }

            $changedProperties[] = $key;
        }

        return array_unique($changedProperties);
    }
}
