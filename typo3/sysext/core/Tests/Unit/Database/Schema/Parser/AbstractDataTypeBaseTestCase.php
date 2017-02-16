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

use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateColumnDefinitionItem;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableStatement;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;

/**
 * Base class for test cases related to parser data types.
 */
abstract class AbstractDataTypeBaseTestCase extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Insert datatype to test into this create table statement
     */
    const CREATE_TABLE_STATEMENT = 'CREATE TABLE `aTable`(`aField` %s);';

    /**
     * Wrap a column definition into a create table statement for testing
     *
     * @param string $columnDefinition
     * @return string
     */
    protected function createTableStatement(string $columnDefinition): string
    {
        return sprintf(static::CREATE_TABLE_STATEMENT, $columnDefinition);
    }

    /**
     * Parse the CREATE TABLE statement and return the reference definition
     *
     * @param string $statement
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateColumnDefinitionItem
     */
    protected function createSubject(string $statement): CreateColumnDefinitionItem
    {
        $parser = new Parser($this->createTableStatement($statement));
        /** @var CreateTableStatement $createTableStatement */
        $createTableStatement = $parser->getAST();

        return $createTableStatement->createDefinition->items[0];
    }
}
