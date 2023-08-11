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
use TYPO3\CMS\Core\Database\Schema\Parser\Lexer;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Base class for test cases related to parser data types.
 */
abstract class AbstractDataTypeBaseTestCase extends UnitTestCase
{
    /**
     * Insert datatype to test into this create table statement
     */
    protected const CREATE_TABLE_STATEMENT = 'CREATE TABLE `aTable`(`aField` %s);';

    /**
     * Parse the CREATE TABLE statement and return the reference definition
     */
    protected function createSubject(string $statement): CreateColumnDefinitionItem
    {
        $statement = sprintf(static::CREATE_TABLE_STATEMENT, $statement);
        $parser = new Parser(new Lexer());
        /** @var CreateTableStatement $createTableStatement */
        $createTableStatement = $parser->getAST($statement);
        /** @var CreateColumnDefinitionItem $item */
        $item = $createTableStatement->createDefinition->items[0];
        return $item;
    }
}
