<?php
declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Unit\Database;

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

use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tests for SqlReader
 */
class SqlReaderTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var SqlReader
     */
    protected $subject;

    /**
     * Set up the test subject
     */
    protected function setUp()
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(SqlReader::class);
    }

    /**
     * @test
     */
    public function getStatementArraySplitsStatements()
    {
        $result = $this->subject->getStatementArray(
            'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) VALUES(1);'
        );
        $this->assertCount(2, $result);
        $this->assertStringStartsWith('CREATE TABLE', $result[0]);
        $this->assertStringStartsWith('INSERT INTO', $result[1]);
    }

    /**
     * @test
     */
    public function getStatementArrayFiltersStatements()
    {
        $result = $this->subject->getStatementArray(
            'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) VALUES(1);',
            '^CREATE TABLE'
        );
        $this->assertCount(1, $result);
        $this->assertStringStartsWith('CREATE TABLE', array_pop($result));
    }

    /**
     * @test
     */
    public function getInsertStatementArrayResult()
    {
        $result = $this->subject->getInsertStatementArray(
            'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) VALUES(1);'
        );

        $this->assertCount(1, $result);
        $this->assertStringStartsWith('INSERT', array_pop($result));
    }

    /**
     * @test
     */
    public function getInsertStatementArrayResultWithNewline()
    {
        $result = $this->subject->getInsertStatementArray(
            'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) ' .
            LF .
            'VALUES(1);'
        );

        $this->assertCount(1, $result);
        $this->assertSame('INSERT INTO aTestTable(`aTestField`) VALUES(1);', array_pop($result));
    }

    /**
     * @test
     */
    public function getCreateTableStatementArrayResult()
    {
        $result = $this->subject->getCreateTableStatementArray(
            'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) VALUES(1);'
        );
        $this->assertCount(1, $result);
        $this->assertStringStartsWith('CREATE TABLE', array_pop($result));
    }
}
