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
 * Tests for CreateColumnDefinitionItem
 */
class ColumnDefinitionItemTest extends UnitTestCase
{
    /**
     * @test
     */
    public function canParseUnquotedMysqlKeywordAsTableName()
    {
        $subject = $this->createSubject('CREATE TABLE `aTable`(checksum VARCHAR(64));');

        self::assertInstanceOf(CreateColumnDefinitionItem::class, $subject);
        self::assertSame($subject->columnName->schemaObjectName, 'checksum');
    }

    /**
     * The old regular expression based create table parser processed invalid dump files
     * where the last column/index definition included a comma before the closing parenthesis.
     * Emulate this behaviour to avoid breaking lots of (partial) dump files.
     *
     * @test
     */
    public function canParseCreateDefinitionWithTrailingComma()
    {
        $subject = $this->createSubject('CREATE TABLE `aTable`(aField VARCHAR(64), );');

        self::assertInstanceOf(CreateColumnDefinitionItem::class, $subject);
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
