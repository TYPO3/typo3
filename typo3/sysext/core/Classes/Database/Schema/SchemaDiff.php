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

use Doctrine\DBAL\Schema\SchemaDiff as DoctrineSchemaDiff;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff as DoctrineTableDiff;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Based on the doctrine/dbal implementation restoring direct property access
 * and adding further helper methods.
 *
 * @internal not part of public Core API.
 */
class SchemaDiff extends DoctrineSchemaDiff
{
    /**
     * Constructs an SchemaDiff object.
     *
     * @internal The diff can be only instantiated by a {@see Comparator}.
     *
     * @param array<string>             $createdSchemas
     * @param array<string>             $droppedSchemas
     * @param array<string, Table>      $createdTables
     * @param array<string, TableDiff>  $alteredTables
     * @param array<string, Table>      $droppedTables
     * @param array<Sequence>           $createdSequences
     * @param array<Sequence>           $alteredSequences
     * @param array<Sequence>           $droppedSequences
     */
    public function __construct(
        public array $createdSchemas,
        public array $droppedSchemas,
        public array $createdTables,
        public array $alteredTables,
        public array $droppedTables,
        public array $createdSequences,
        public array $alteredSequences,
        public array $droppedSequences,
    ) {
        $this->alteredTables = array_filter($alteredTables, static function (TableDiff $diff): bool {
            return !$diff->isEmpty();
        });
        // NOTE: parent::__construct() not called by intention.
    }

    /** @return array<string> */
    public function getCreatedSchemas(): array
    {
        return $this->createdSchemas;
    }

    /** @return array<string> */
    public function getDroppedSchemas(): array
    {
        return $this->droppedSchemas;
    }

    /** @return array<string, Table> */
    public function getCreatedTables(): array
    {
        return $this->createdTables;
    }

    /** @return array<string, TableDiff> */
    public function getAlteredTables(): array
    {
        return $this->alteredTables;
    }

    /** @return array<string, Table> */
    public function getDroppedTables(): array
    {
        return $this->droppedTables;
    }

    /** @return array<Sequence> */
    public function getCreatedSequences(): array
    {
        return $this->createdSequences;
    }

    /** @return array<Sequence> */
    public function getAlteredSequences(): array
    {
        return $this->alteredSequences;
    }

    /** @return array<Sequence> */
    public function getDroppedSequences(): array
    {
        return $this->droppedSequences;
    }

    /**
     * Returns whether the diff is empty (contains no changes).
     */
    public function isEmpty(): bool
    {
        return count($this->createdSchemas) === 0
            && count($this->droppedSchemas) === 0
            && count($this->createdTables) === 0
            && count($this->alteredTables) === 0
            && count($this->droppedTables) === 0
            && count($this->createdSequences) === 0
            && count($this->alteredSequences) === 0
            && count($this->droppedSequences) === 0;
    }

    public static function ensure(SchemaDiff|DoctrineSchemaDiff $schemaDiff, array $additionalArguments = []): self
    {
        return new self(...[
            'createdSchemas' => $schemaDiff->getCreatedSchemas(),
            'droppedSchemas' => $schemaDiff->getDroppedSchemas(),
            'createdTables' => self::ensureCollection(...$schemaDiff->getCreatedTables()),
            'alteredTables' => self::ensureCollection(...$schemaDiff->getAlteredTables()),
            'droppedTables' => self::ensureCollection(...$schemaDiff->getDroppedTables()),
            'createdSequences' => $schemaDiff->getCreatedSequences(),
            'alteredSequences' => $schemaDiff->getAlteredSequences(),
            'droppedSequences' => $schemaDiff->getDroppedSequences(),
            ...$additionalArguments,
        ]);
    }

    /**
     * @param DoctrineTableDiff|TableDiff|Table ...$tableDiffs
     * @return TableDiff[]|Table[]
     */
    public static function ensureCollection(DoctrineTableDiff|TableDiff|Table ...$tableDiffs): array
    {
        $collection = [];
        foreach ($tableDiffs as $key => $tableDiff) {
            if ($tableDiff instanceof DoctrineTableDiff) {
                $tableDiff = TableDiff::ensure($tableDiff);
            }
            if (is_int($key) || MathUtility::canBeInterpretedAsInteger($key)) {
                $key = $tableDiff instanceof Table
                    ? $tableDiff->getName()
                    : $tableDiff->getOldTable()->getName();
            }
            $collection[$key] = $tableDiff;
        }
        return $collection;
    }
}
