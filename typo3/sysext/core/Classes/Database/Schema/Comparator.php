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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
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
}
