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

use TYPO3\CMS\Core\Database\Schema\Parser\AST\AbstractCreateStatement;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableStatement;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for CreateTableStatement
 */
class CreateTableFragmentTest extends UnitTestCase
{
    /**
     * Each parameter array consists of the following values:
     *  - create table SQL fragment
     *  - table name
     *  - is temporary
     *
     * @return array
     */
    public function canParseCreateTableFragmentDataProvider(): array
    {
        return [
            'CREATE TABLE' => [
                'CREATE TABLE aTable (aField INT);',
                'aTable',
                false
            ],
            'CREATE TEMPORARY TABLE' => [
                'CREATE TEMPORARY TABLE aTable (aField INT);',
                'aTable',
                true
            ],
            'CREATE TABLE IF NOT EXISTS' => [
                'CREATE TABLE IF NOT EXISTS aTable (aField INT);',
                'aTable',
                false
            ],
            'CREATE TEMPORARY TABLE IF NOT EXISTS' => [
                'CREATE TEMPORARY TABLE IF NOT EXISTS aTable (aField INT);',
                'aTable',
                true
            ],
            'CREATE TABLE (quoted table name)' => [
                'CREATE TABLE `aTable` (aField INT);',
                'aTable',
                false
            ],
            'CREATE TEMPORARY TABLE (quoted table name)' => [
                'CREATE TEMPORARY TABLE `aTable` (aField INT);',
                'aTable',
                true
            ],
            'CREATE TABLE IF NOT EXISTS (quoted table name)' => [
                'CREATE TABLE IF NOT EXISTS `aTable` (aField INT);',
                'aTable',
                false
            ],
            'CREATE TEMPORARY TABLE IF NOT EXISTS (quoted table name)' => [
                'CREATE TEMPORARY TABLE IF NOT EXISTS `aTable` (aField INT);',
                'aTable',
                true
            ],
        ];
    }

    /**
     * @test
     * @dataProvider canParseCreateTableFragmentDataProvider
     * @param string $statement
     * @param string $tableName
     * @param bool $isTemporary
     */
    public function canParseCreateTableFragment(string $statement, string $tableName, bool $isTemporary)
    {
        $subject = $this->createSubject($statement);
        self::assertInstanceOf(CreateTableStatement::class, $subject);
        self::assertSame($tableName, $subject->tableName->schemaObjectName);
        self::assertSame($isTemporary, $subject->isTemporary);
    }

    /**
     * Parse the CREATE TABLE statement and return the reference definition
     *
     * @param string $statement
     * @return AbstractCreateStatement|CreateTableStatement
     */
    protected function createSubject(string $statement): AbstractCreateStatement
    {
        $parser = new Parser($statement);
        return $parser->getAST();
    }
}
