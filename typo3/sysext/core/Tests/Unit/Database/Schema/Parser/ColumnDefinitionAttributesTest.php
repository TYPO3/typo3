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

use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateColumnDefinitionItem;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableStatement;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for CreateColumnDefinitionItem attributes
 */
class ColumnDefinitionAttributesTest extends UnitTestCase
{
    /**
     * Each parameter array consists of the following values:
     *  - column definition attributes SQL fragment
     *  - allow null values
     *  - has default value
     *  - default value
     *  - auto increment column
     *  - create index on column
     *  - create unique index column
     *  - use column as primary key
     *  - comment
     *  - column format
     *  - storage
     *
     * @return array
     */
    public function canParseColumnDefinitionAttributesDataProvider(): array
    {
        return [
            'NULL' => [
                'NULL',
                true,
                false,
                null,
                false,
                false,
                false,
                false,
                null,
                null,
                null,
            ],
            'NOT NULL' => [
                'NOT NULL',
                false,
                false,
                null,
                false,
                false,
                false,
                false,
                null,
                null,
                null,
            ],
            'DEFAULT' => [
                "DEFAULT '0'",
                true,
                true,
                '0',
                false,
                false,
                false,
                false,
                null,
                null,
                null,
            ],
            'AUTO_INCREMENT' => [
                'AUTO_INCREMENT',
                true,
                false,
                null,
                true,
                false,
                false,
                false,
                null,
                null,
                null,
            ],
            'UNIQUE' => [
                'UNIQUE',
                true,
                false,
                null,
                false,
                false,
                true,
                false,
                null,
                null,
                null,
            ],
            'UNIQUE KEY' => [
                'UNIQUE KEY',
                true,
                false,
                null,
                false,
                false,
                true,
                false,
                null,
                null,
                null,
            ],
            'PRIMARY' => [
                'PRIMARY',
                true,
                false,
                null,
                false,
                false,
                false,
                true,
                null,
                null,
                null,
            ],
            'PRIMARY KEY' => [
                'PRIMARY KEY',
                true,
                false,
                null,
                false,
                false,
                false,
                true,
                null,
                null,
                null,
            ],
            'KEY' => [
                'KEY',
                true,
                false,
                null,
                false,
                true,
                false,
                false,
                null,
                null,
                null,
            ],
            'COMMENT' => [
                "COMMENT 'aComment'",
                true,
                false,
                null,
                false,
                false,
                false,
                false,
                'aComment',
                null,
                null,
            ],
            'COLUMN_FORMAT FIXED' => [
                'COLUMN_FORMAT FIXED',
                true,
                false,
                null,
                false,
                false,
                false,
                false,
                null,
                'fixed',
                null,
            ],
            'COLUMN_FORMAT DYNAMIC' => [
                'COLUMN_FORMAT DYNAMIC',
                true,
                false,
                null,
                false,
                false,
                false,
                false,
                null,
                'dynamic',
                null,
            ],
            'COLUMN_FORMAT DEFAULT' => [
                'COLUMN_FORMAT DEFAULT',
                true,
                false,
                null,
                false,
                false,
                false,
                false,
                null,
                null,
                null,
            ],
            'STORAGE DISK' => [
                'STORAGE DISK',
                true,
                false,
                null,
                false,
                false,
                false,
                false,
                null,
                null,
                'disk',
            ],
            'STORAGE MEMORY' => [
                'STORAGE MEMORY',
                true,
                false,
                null,
                false,
                false,
                false,
                false,
                null,
                null,
                'memory',
            ],
            'STORAGE DEFAULT' => [
                'STORAGE DEFAULT',
                true,
                false,
                null,
                false,
                false,
                false,
                false,
                null,
                null,
                null,
            ],
            "NOT NULL DEFAULT '0'" => [
                "NOT NULL DEFAULT '0'",
                false,
                true,
                '0',
                false,
                false,
                false,
                false,
                null,
                null,
                null,
            ],
            'NOT NULL AUTO_INCREMENT' => [
                'NOT NULL AUTO_INCREMENT',
                false,
                false,
                null,
                true,
                false,
                false,
                false,
                null,
                null,
                null,
            ],
            'NULL DEFAULT NULL' => [
                'NULL DEFAULT NULL',
                true,
                true,
                null,
                false,
                false,
                false,
                false,
                null,
                null,
                null,
            ],
            'NOT NULL PRIMARY KEY' => [
                'NOT NULL PRIMARY KEY',
                false,
                false,
                null,
                false,
                false,
                false,
                true,
                null,
                null,
                null,
            ],
            "NULL DEFAULT 'dummy' UNIQUE" => [
                "NULL DEFAULT 'dummy' UNIQUE",
                true,
                true,
                'dummy',
                false,
                false,
                true,
                false,
                null,
                null,
                null,
            ],
            "NOT NULL DEFAULT '0' COMMENT 'aComment with blanks' AUTO_INCREMENT PRIMARY KEY COLUMN_FORMAT DYNAMIC" => [
                "NOT NULL DEFAULT '0' COMMENT 'aComment with blanks' AUTO_INCREMENT PRIMARY KEY COLUMN_FORMAT DYNAMIC",
                false,
                true,
                '0',
                true,
                false,
                false,
                true,
                'aComment with blanks',
                'dynamic',
                null,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseColumnDefinitionAttributesDataProvider
     * @param string $columnAttribute
     * @param bool $allowNull
     * @param bool $hasDefaultValue
     * @param mixed $defaultValue
     * @param bool $autoIncrement
     * @param bool $createIndex
     * @param bool $createUniqueIndex
     * @param bool $isPrimaryKey
     * @param string $comment
     * @param string $columnFormat
     * @param string $storage
     */
    public function canParseColumnDefinitionAttributes(
        string $columnAttribute,
        bool $allowNull,
        bool $hasDefaultValue,
        $defaultValue,
        bool $autoIncrement,
        bool $createIndex,
        bool $createUniqueIndex,
        bool $isPrimaryKey,
        string $comment = null,
        string $columnFormat = null,
        string $storage = null
    ) {
        $statement = sprintf('CREATE TABLE `aTable`(`aField` INT(11) %s);', $columnAttribute);
        $subject = $this->createSubject($statement);

        self::assertInstanceOf(CreateColumnDefinitionItem::class, $subject);
        self::assertSame($allowNull, $subject->allowNull);
        self::assertSame($hasDefaultValue, $subject->hasDefaultValue);
        self::assertSame($defaultValue, $subject->defaultValue);
        self::assertSame($createIndex, $subject->index);
        self::assertSame($createUniqueIndex, $subject->unique);
        self::assertSame($isPrimaryKey, $subject->primary);
        self::assertSame($autoIncrement, $subject->autoIncrement);
        self::assertSame($comment, $subject->comment);
        self::assertSame($columnFormat, $subject->columnFormat);
        self::assertSame($storage, $subject->storage);
    }

    /**
     * Parse the CREATE TABLE statement and return the reference definition
     *
     * @param string $statement
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateColumnDefinitionItem
     */
    protected function createSubject(string $statement): CreateColumnDefinitionItem
    {
        $parser = new Parser($statement);
        /** @var CreateTableStatement $createTableStatement */
        $createTableStatement = $parser->getAST();

        return $createTableStatement->createDefinition->items[0];
    }
}
