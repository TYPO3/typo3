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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\AbstractCreateDefinitionItem;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateColumnDefinitionItem;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableStatement;
use TYPO3\CMS\Core\Database\Schema\Parser\Lexer;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for CreateColumnDefinitionItem attributes
 */
final class ColumnDefinitionAttributesTest extends UnitTestCase
{
    /**
     * @return array<string, array{
     *     columnAttribute: string,
     *     allowNull: bool,
     *     hasDefaultValue: bool,
     *     defaultValue: mixed,
     *     autoIncrement: bool,
     *     createIndex: bool,
     *     createUniqueIndex: bool,
     *     isPrimaryKey: bool,
     *     comment: string|null,
     *     columnFormat: string|null,
     *     storage: string|null
     * }>
     */
    public static function canParseColumnDefinitionAttributesDataProvider(): array
    {
        return [
            'NULL' => [
                'columnAttribute' => 'NULL',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'NOT NULL' => [
                'columnAttribute' => 'NOT NULL',
                'allowNull' => false,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'DEFAULT' => [
                'columnAttribute' => "DEFAULT '0'",
                'allowNull' => true,
                'hasDefaultValue' => true,
                'defaultValue' => '0',
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'AUTO_INCREMENT' => [
                'columnAttribute' => 'AUTO_INCREMENT',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => true,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'UNIQUE' => [
                'columnAttribute' => 'UNIQUE',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => true,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'UNIQUE KEY' => [
                'columnAttribute' => 'UNIQUE KEY',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => true,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'PRIMARY' => [
                'columnAttribute' => 'PRIMARY',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => true,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'PRIMARY KEY' => [
                'columnAttribute' => 'PRIMARY KEY',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => true,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'KEY' => [
                'columnAttribute' => 'KEY',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => true,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'COMMENT' => [
                'columnAttribute' => "COMMENT 'aComment'",
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => 'aComment',
                'columnFormat' => null,
                'storage' => null,
            ],
            'COLUMN_FORMAT FIXED' => [
                'columnAttribute' => 'COLUMN_FORMAT FIXED',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => 'fixed',
                'storage' => null,
            ],
            'COLUMN_FORMAT DYNAMIC' => [
                'columnAttribute' => 'COLUMN_FORMAT DYNAMIC',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => 'dynamic',
                'storage' => null,
            ],
            'COLUMN_FORMAT DEFAULT' => [
                'columnAttribute' => 'COLUMN_FORMAT DEFAULT',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'STORAGE DISK' => [
                'columnAttribute' => 'STORAGE DISK',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => 'disk',
            ],
            'STORAGE MEMORY' => [
                'columnAttribute' => 'STORAGE MEMORY',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => 'memory',
            ],
            'STORAGE DEFAULT' => [
                'columnAttribute' => 'STORAGE DEFAULT',
                'allowNull' => true,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            "NOT NULL DEFAULT '0'" => [
                'columnAttribute' => "NOT NULL DEFAULT '0'",
                'allowNull' => false,
                'hasDefaultValue' => true,
                'defaultValue' => '0',
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'NOT NULL AUTO_INCREMENT' => [
                'columnAttribute' => 'NOT NULL AUTO_INCREMENT',
                'allowNull' => false,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => true,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'NULL DEFAULT NULL' => [
                'columnAttribute' => 'NULL DEFAULT NULL',
                'allowNull' => true,
                'hasDefaultValue' => true,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            'NOT NULL PRIMARY KEY' => [
                'columnAttribute' => 'NOT NULL PRIMARY KEY',
                'allowNull' => false,
                'hasDefaultValue' => false,
                'defaultValue' => null,
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => true,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            "NULL DEFAULT 'dummy' UNIQUE" => [
                'columnAttribute' => "NULL DEFAULT 'dummy' UNIQUE",
                'allowNull' => true,
                'hasDefaultValue' => true,
                'defaultValue' => 'dummy',
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => true,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            "NOT NULL DEFAULT '0' COMMENT 'aComment with blanks' AUTO_INCREMENT PRIMARY KEY COLUMN_FORMAT DYNAMIC" => [
                'columnAttribute' => "NOT NULL DEFAULT '0' COMMENT 'aComment with blanks' AUTO_INCREMENT PRIMARY KEY COLUMN_FORMAT DYNAMIC",
                'allowNull' => false,
                'hasDefaultValue' => true,
                'defaultValue' => '0',
                'autoIncrement' => true,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => true,
                'comment' => 'aComment with blanks',
                'columnFormat' => 'dynamic',
                'storage' => null,
            ],
            // MySQL and MariaDB way to quote ' for a value string is to use double single-quotes "''" instead of
            // using some sort of escape sequences, which is covered by the following testcase.
            "DEFAULT 'quoted single-quote '' can be parsed'" => [
                'columnAttribute' => "DEFAULT 'quoted single-quote ('') can be parsed'",
                'allowNull' => true,
                'hasDefaultValue' => true,
                'defaultValue' => "quoted single-quote (') can be parsed",
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
            "DEFAULT 'double-quote (\") can be parsed'" => [
                'columnAttribute' => "DEFAULT 'quoted single-quote (\") can be parsed'",
                'allowNull' => true,
                'hasDefaultValue' => true,
                'defaultValue' => 'quoted single-quote (") can be parsed',
                'autoIncrement' => false,
                'createIndex' => false,
                'createUniqueIndex' => false,
                'isPrimaryKey' => false,
                'comment' => null,
                'columnFormat' => null,
                'storage' => null,
            ],
        ];
    }

    #[DataProvider('canParseColumnDefinitionAttributesDataProvider')]
    #[Test]
    public function canParseColumnDefinitionAttributes(
        string $columnAttribute,
        bool $allowNull,
        bool $hasDefaultValue,
        mixed $defaultValue,
        bool $autoIncrement,
        bool $createIndex,
        bool $createUniqueIndex,
        bool $isPrimaryKey,
        string $comment = null,
        string $columnFormat = null,
        string $storage = null
    ): void {
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
     */
    private function createSubject(string $statement): AbstractCreateDefinitionItem
    {
        $parser = new Parser(new Lexer());
        /** @var CreateTableStatement $createTableStatement */
        $createTableStatement = $parser->getAST($statement);
        return $createTableStatement->createDefinition->items[0];
    }
}
