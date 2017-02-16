<?php
declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\Parser;

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

use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateIndexDefinitionItem;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableStatement;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\Identifier;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;

/**
 * Tests for CreateIndexDefinitionItem
 */
class IndexDefinitionTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Each parameter array consists of the following values:
     *  - index definition SQL fragment
     *  - expected index name
     *  - array of index column definitions [name, length, direction]
     *  - isPrimary
     *  - isUnique
     *  - isFulltext
     *  - isSpatial
     *  - indexType
     *  - options array
     *
     * @return array
     */
    public function canParseIndexDefinitionDataProvider(): array
    {
        return [
            'PRIMARY KEY (single column)' => [
                'PRIMARY KEY (`aField`)',
                '',
                [['aField', 0, null]],
                true,
                false,
                false,
                false,
                '',
                [],
            ],
            'PRIMARY KEY (multiple columns)' => [
                'PRIMARY KEY (`aField`, `bField`(199), cField)',
                '',
                [['aField', 0, null], ['bField', 199, null], ['cField', 0, null]],
                true,
                false,
                false,
                false,
                '',
                [],
            ],
            'PRIMARY KEY (index type)' => [
                'PRIMARY KEY USING HASH (`aField`)',
                '',
                [['aField', 0, null]],
                true,
                false,
                false,
                false,
                'HASH',
                [],
            ],
            'PRIMARY KEY (index options)' => [
                "PRIMARY KEY (`aField`, bField(199)) KEY_BLOCK_SIZE 4 WITH PARSER `something` COMMENT 'aTest'",
                '',
                [['aField', 0, null], ['bField', 199, null]],
                true,
                false,
                false,
                false,
                '',
                [
                    'key_block_size' => 4,
                    'parser' => new Identifier('something'),
                    'comment' => 'aTest',
                ],
            ],
            'PRIMARY KEY (all parts)' => [
                "PRIMARY KEY USING BTREE (`aField`, bField(199)) KEY_BLOCK_SIZE 4 COMMENT 'aTest'",
                '',
                [['aField', 0, null], ['bField', 199, null]],
                true,
                false,
                false,
                false,
                'BTREE',
                [
                    'key_block_size' => 4,
                    'comment' => 'aTest',
                ],
            ],
            'INDEX (single column)' => [
                'INDEX (`aField`(24))',
                '',
                [['aField', 24, null]],
                false,
                false,
                false,
                false,
                '',
                [],
            ],
            'INDEX (multiple columns)' => [
                'INDEX (`aField`(24), bField)',
                '',
                [['aField', 24, null], ['bField', 0, null]],
                false,
                false,
                false,
                false,
                '',
                [],
            ],
            'INDEX (index name)' => [
                'INDEX aIndex (`aField`)',
                'aIndex',
                [['aField', 0, null]],
                false,
                false,
                false,
                false,
                '',
                [],
            ],
            'INDEX (index type)' => [
                'INDEX USING HASH (`aField`)',
                '',
                [['aField', 0, null]],
                false,
                false,
                false,
                false,
                'HASH',
                [],
            ],
            'INDEX (index name & type)' => [
                'INDEX `aIndex` USING BTREE (`aField`)',
                'aIndex',
                [['aField', 0, null]],
                false,
                false,
                false,
                false,
                'BTREE',
                [],
            ],
            'INDEX (all parts)' => [
                "INDEX `aIndex` USING BTREE (`aField`) COMMENT 'aComment'",
                'aIndex',
                [['aField', 0, null]],
                false,
                false,
                false,
                false,
                'BTREE',
                [
                    'comment' => 'aComment',
                ],
            ],
            'KEY (single column)' => [
                'KEY (`aField`(24))',
                '',
                [['aField', 24, null]],
                false,
                false,
                false,
                false,
                '',
                [],
            ],
            'KEY (multiple columns)' => [
                'KEY (`aField`(24), bField)',
                '',
                [['aField', 24, null], ['bField', 0, null]],
                false,
                false,
                false,
                false,
                '',
                [],
            ],
            'KEY (index name)' => [
                'KEY aIndex (`aField`)',
                'aIndex',
                [['aField', 0, null]],
                false,
                false,
                false,
                false,
                '',
                [],
            ],
            'KEY (index type)' => [
                'KEY USING BTREE (aField(96))',
                '',
                [['aField', 96, null]],
                false,
                false,
                false,
                false,
                'BTREE',
                [],
            ],
            'KEY (index name & type)' => [
                'KEY `aIndex` USING HASH (`aField`)',
                'aIndex',
                [['aField', 0, null]],
                false,
                false,
                false,
                false,
                'HASH',
                [],
            ],
            'KEY (all parts)' => [
                'KEY `aIndex` USING HASH (`aField`) WITH PARSER aParser',
                'aIndex',
                [['aField', 0, null]],
                false,
                false,
                false,
                false,
                'HASH',
                [
                    'parser' => new Identifier('aParser'),
                ],
            ],
            'UNIQUE (single column)' => [
                'UNIQUE (`aField`)',
                '',
                [['aField', 0, null]],
                false,
                true,
                false,
                false,
                '',
                [],
            ],
            'UNIQUE (multiple columns)' => [
                'UNIQUE (`aField`, bField, cField(40))',
                '',
                [['aField', 0, null], ['bField', 0, null], ['cField', 40, null]],
                false,
                true,
                false,
                false,
                '',
                [],
            ],
            'UNIQUE INDEX (single column)' => [
                'UNIQUE INDEX (`aField`)',
                '',
                [['aField', 0, null]],
                false,
                true,
                false,
                false,
                '',
                [],
            ],
            'UNIQUE KEY (multiple columns)' => [
                'UNIQUE KEY (`aField`, bField, cField(40))',
                '',
                [['aField', 0, null], ['bField', 0, null], ['cField', 40, null]],
                false,
                true,
                false,
                false,
                '',
                [],
            ],
            'UNIQUE (index name)' => [
                'UNIQUE aIndex (`aField`)',
                'aIndex',
                [['aField', 0, null]],
                false,
                true,
                false,
                false,
                '',
                [],
            ],
            'UNIQUE (index type)' => [
                'UNIQUE USING BTREE (`aField`)',
                '',
                [['aField', 0, null]],
                false,
                true,
                false,
                false,
                'BTREE',
                [],
            ],
            'UNIQUE (index name & type)' => [
                'UNIQUE `aIndex` USING BTREE (`aField`)',
                'aIndex',
                [['aField', 0, null]],
                false,
                true,
                false,
                false,
                'BTREE',
                [],
            ],
            'UNIQUE (all parts)' => [
                'UNIQUE `aIndex` USING BTREE (`aField`) KEY_BLOCK_SIZE = 24',
                'aIndex',
                [['aField', 0, null]],
                false,
                true,
                false,
                false,
                'BTREE',
                [
                    'key_block_size' => 24,
                ],
            ],
            'FULLTEXT (single column)' => [
                'FULLTEXT (`aField`)',
                '',
                [['aField', 0, null]],
                false,
                false,
                true,
                false,
                '',
                [],
            ],
            'FULLTEXT (multiple columns)' => [
                'FULLTEXT (`aField`, `bField`)',
                '',
                [['aField', 0, null], ['bField', 0, null]],
                false,
                false,
                true,
                false,
                '',
                [],
            ],
            'FULLTEXT (index name)' => [
                'FULLTEXT aIndex (`aField`, `bField`)',
                'aIndex',
                [['aField', 0, null], ['bField', 0, null]],
                false,
                false,
                true,
                false,
                '',
                [],
            ],
            'FULLTEXT (all parts)' => [
                "FULLTEXT `aIndex` (`aField`, `bField`) COMMENT 'aComment'",
                'aIndex',
                [['aField', 0, null], ['bField', 0, null]],
                false,
                false,
                true,
                false,
                '',
                [
                    'comment' => 'aComment',
                ],
            ],
            'FULLTEXT INDEX (single column)' => [
                'FULLTEXT INDEX (`aField`)',
                '',
                [['aField', 0, null]],
                false,
                false,
                true,
                false,
                '',
                [],
            ],
            'FULLTEXT INDEX (multiple columns)' => [
                'FULLTEXT INDEX (`aField`, bField(19))',
                '',
                [['aField', 0, null], ['bField', 19, null]],
                false,
                false,
                true,
                false,
                '',
                [],
            ],
            'FULLTEXT KEY (single column)' => [
                'FULLTEXT KEY (aField(20))',
                '',
                [['aField', 20, null]],
                false,
                false,
                true,
                false,
                '',
                [],
            ],
            'FULLTEXT KEY (multiple columns)' => [
                'FULLTEXT KEY (aField(20), `bField`)',
                '',
                [['aField', 20, null], ['bField', 0, null]],
                false,
                false,
                true,
                false,
                '',
                [],
            ],
            'SPATIAL (single column)' => [
                'SPATIAL (`aField`)',
                '',
                [['aField', 0, null]],
                false,
                false,
                false,
                true,
                '',
                [],
            ],
            'SPATIAL (multiple columns)' => [
                'SPATIAL (`aField`, `bField`)',
                '',
                [['aField', 0, null], ['bField', 0, null]],
                false,
                false,
                false,
                true,
                '',
                [],
            ],
            'SPATIAL (index name)' => [
                'SPATIAL `aIndex` (`aField`, `bField`)',
                'aIndex',
                [['aField', 0, null], ['bField', 0, null]],
                false,
                false,
                false,
                true,
                '',
                [],
            ],
            'SPATIAL (all parts)' => [
                "SPATIAL `aIndex` (`aField`, `bField`) WITH PARSER aParser COMMENT 'aComment'",
                'aIndex',
                [['aField', 0, null], ['bField', 0, null]],
                false,
                false,
                false,
                true,
                '',
                [
                    'parser' => new Identifier('aParser'),
                    'comment' => 'aComment',
                ],
            ],
            'SPATIAL INDEX (single column)' => [
                'SPATIAL INDEX (`aField`)',
                '',
                [['aField', 0, null]],
                false,
                false,
                false,
                true,
                '',
                [],
            ],
            'SPATIAL INDEX (multiple columns)' => [
                'SPATIAL INDEX (aField, bField)',
                '',
                [['aField', 0, null], ['bField', 0, null]],
                false,
                false,
                false,
                true,
                '',
                [],
            ],
            'SPATIAL KEY (single column)' => [
                'SPATIAL KEY (aField)',
                '',
                [['aField', 0, null]],
                false,
                false,
                false,
                true,
                '',
                [],
            ],
            'SPATIAL KEY (multiple columns)' => [
                'SPATIAL KEY (aField, bField(240))',
                '',
                [['aField', 0, null], ['bField', 240, null]],
                false,
                false,
                false,
                true,
                '',
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseIndexDefinitionDataProvider
     * @param string $indexDefinition
     * @param string $indexName
     * @param array $indexColumns
     * @param bool $isPrimary
     * @param bool $isUnique
     * @param bool $isFulltext
     * @param bool $isSpatial
     * @param string $indexType
     * @param array $indexOptions
     */
    public function canParseIndexDefinition(
        string $indexDefinition,
        string $indexName,
        array $indexColumns,
        bool $isPrimary,
        bool $isUnique,
        bool $isFulltext,
        bool $isSpatial,
        string $indexType,
        array $indexOptions
    ) {
        $statement = sprintf('CREATE TABLE `aTable`(`aField` INT(11), %s);', $indexDefinition);
        $subject = $this->createSubject($statement);

        $this->assertInstanceOf(CreateIndexDefinitionItem::class, $subject);
        $this->assertSame($indexName, $subject->indexName->schemaObjectName);
        $this->assertSame($isPrimary, $subject->isPrimary);
        $this->assertSame($isUnique, $subject->isUnique);
        $this->assertSame($isFulltext, $subject->isFulltext);
        $this->assertSame($isSpatial, $subject->isSpatial);
        $this->assertSame($indexType, $subject->indexType);
        $this->assertEquals($indexOptions, $subject->options);

        foreach ($indexColumns as $index => $column) {
            $this->assertSame($column[0], $subject->columnNames[$index]->columnName->schemaObjectName);
            $this->assertSame($column[1], $subject->columnNames[$index]->length);
            $this->assertSame($column[2], $subject->columnNames[$index]->direction);
        }
    }

    /**
     * Parse the CREATE TABLE statement and return the reference definition
     *
     * @param string $statement
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateIndexDefinitionItem
     */
    protected function createSubject(string $statement): CreateIndexDefinitionItem
    {
        $parser = new Parser($statement);
        /** @var CreateTableStatement $createTableStatement */
        $createTableStatement = $parser->getAST();

        return $createTableStatement->createDefinition->items[1];
    }
}
