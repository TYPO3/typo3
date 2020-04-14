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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser;

use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateForeignKeyDefinitionItem;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableStatement;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for CreateForeignKeyDefinitionItem
 */
class ForeignKeyDefinitionTest extends UnitTestCase
{
    /**
     * Each parameter array consists of the following values:
     *  - index definition SQL fragment
     *  - index name
     *  - array of index column definitions [name, length, direction]
     *  - foreign table name
     *  - array of foreign column definitions [name, length, direction]
     *
     * @return array
     */
    public function canParseForeignKeyDefinitionDataProvider(): array
    {
        return [
            // See ReferenceDefinitionTest for actual reference definition parsing tests
            'FOREIGN KEY (single column)' => [
                'FOREIGN KEY (`aField`) REFERENCES `bTable` (`bField`)',
                '',
                [['aField', 0, null]],
                'bTable',
                [['bField', 0, null]],
            ],
            'FOREIGN KEY (multiple columns)' => [
                'FOREIGN KEY (`aField`(20) ASC, `bField`) REFERENCES `bTable` (`cField`, `dField`)',
                '',
                [['aField', 20, 'ASC'], ['bField', 0, null]],
                'bTable',
                [['cField', 0, null], ['dField', 0, null]],
            ],
            'FOREIGN KEY (index name)' => [
                'FOREIGN KEY `aIndex`(`aField`, `bField`) REFERENCES `bTable` (`cField`(240) DESC, `dField`)',
                'aIndex',
                [['aField', 0, null], ['bField', 0, null]],
                'bTable',
                [['cField', 240, 'DESC'], ['dField', 0, null]],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseForeignKeyDefinitionDataProvider
     * @param string $indexDefinition
     * @param string $indexName
     * @param array $indexColumns
     * @param string $foreignTableName
     * @param array $foreignTableColumns
     */
    public function canParseForeignKeyDefinition(
        string $indexDefinition,
        string $indexName,
        array $indexColumns,
        string $foreignTableName,
        array $foreignTableColumns
    ) {
        $statement = sprintf('CREATE TABLE `aTable`(`aField` INT(11), %s);', $indexDefinition);
        $subject = $this->createSubject($statement);

        self::assertInstanceOf(CreateForeignKeyDefinitionItem::class, $subject);
        self::assertSame($indexName, $subject->indexName->schemaObjectName);
        self::assertSame($foreignTableName, $subject->reference->tableName->schemaObjectName);

        foreach ($indexColumns as $index => $column) {
            self::assertSame($column[0], $subject->columnNames[$index]->columnName->schemaObjectName);
            self::assertSame($column[1], $subject->columnNames[$index]->length);
            self::assertSame($column[2], $subject->columnNames[$index]->direction);
        }

        foreach ($foreignTableColumns as $index => $column) {
            self::assertSame($column[0], $subject->reference->columnNames[$index]->columnName->schemaObjectName);
            self::assertSame($column[1], $subject->reference->columnNames[$index]->length);
            self::assertSame($column[2], $subject->reference->columnNames[$index]->direction);
        }
    }

    /**
     * Parse the CREATE TABLE statement and return the reference definition
     *
     * @param string $statement
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateForeignKeyDefinitionItem
     */
    protected function createSubject(string $statement): CreateForeignKeyDefinitionItem
    {
        $parser = new Parser($statement);
        /** @var CreateTableStatement $createTableStatement */
        $createTableStatement = $parser->getAST();

        return $createTableStatement->createDefinition->items[1];
    }
}
