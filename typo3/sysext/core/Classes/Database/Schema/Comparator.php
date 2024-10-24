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

use Doctrine\DBAL\Schema\Comparator as DoctrineComparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\TableDiff as DoctrineTableDiff;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Compares two Schemas and returns an instance of SchemaDiff.
 *
 * @internal not part of public core API.
 */
final readonly class Comparator
{
    public function __construct(
        private DoctrineComparator $comparator
    ) {}

    public function compareSchemas(Schema $oldSchema, Schema $newSchema): SchemaDiff
    {
        $schemaDiff = $this->comparator->compareSchemas($oldSchema, $newSchema);

        $alteredTables = $this->mapAlteredTablesToTypo3TableDiff($schemaDiff->getAlteredTables());
        $alteredTables = SchemaDiff::ensureCollection(...$alteredTables);
        $alteredTables = $this->compareTableOptions($oldSchema, $newSchema, $alteredTables);

        return SchemaDiff::ensure(
            $schemaDiff,
            [
                'alteredTables' => $alteredTables,
            ]
        );
    }

    /**
     * @param array<DoctrineTableDiff> $alteredTables
     * @return array<TableDiff>
     */
    private function mapAlteredTablesToTypo3TableDiff(array $alteredTables): array
    {
        return array_map(
            static fn(DoctrineTableDiff $tableDiff): TableDiff => TableDiff::ensure($tableDiff),
            $alteredTables
        );
    }

    /**
     * Provide change information about table options like the ENGINE (#77786)
     * which are not implemented by doctrine/dbal itself
     *
     * @param array<string, TableDiff> $alteredTables
     * @return array<string, TableDiff>
     */
    private function compareTableOptions(Schema $oldSchema, Schema $newSchema, array $alteredTables): array
    {
        foreach ($newSchema->getTables() as $newTable) {
            $newTableName = $newTable->getShortestName($newSchema->getName());
            if (!$oldSchema->hasTable($newTableName)) {
                // new table, no ALTER TABLE needed
                continue;
            }

            $oldTable = $oldSchema->getTable($newTableName);
            $newTableOptions = array_merge($oldTable->getOptions(), $newTable->getOptions());
            $optionDiff = ArrayUtility::arrayDiffAssocRecursive($newTableOptions, $oldTable->getOptions());
            if ($optionDiff === []) {
                continue;
            }

            $key = $newTable->getName();
            $tableDiff = $alteredTables[$key] ?? new TableDiff($newTable);
            $tableDiff->setTableOptions($optionDiff);
            $alteredTables[$key] = $tableDiff;
        }

        return $alteredTables;
    }
}
