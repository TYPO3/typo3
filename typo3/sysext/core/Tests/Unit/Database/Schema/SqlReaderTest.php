<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema;

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
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SqlReaderTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function getStatementArraySplitsStatements()
    {
        $subject = new SqlReader($this->prophesize(Dispatcher::class)->reveal(), $this->prophesize(PackageManager::class)->reveal());
        $result = $subject->getStatementArray(
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
        $subject = new SqlReader($this->prophesize(Dispatcher::class)->reveal(), $this->prophesize(PackageManager::class)->reveal());
        $result = $subject->getStatementArray(
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
        $subject = new SqlReader($this->prophesize(Dispatcher::class)->reveal(), $this->prophesize(PackageManager::class)->reveal());
        $result = $subject->getInsertStatementArray(
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
        $subject = new SqlReader($this->prophesize(Dispatcher::class)->reveal(), $this->prophesize(PackageManager::class)->reveal());
        $result = $subject->getInsertStatementArray(
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
        $subject = new SqlReader($this->prophesize(Dispatcher::class)->reveal(), $this->prophesize(PackageManager::class)->reveal());
        $result = $subject->getCreateTableStatementArray(
            'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) VALUES(1);'
        );
        $this->assertCount(1, $result);
        $this->assertStringStartsWith('CREATE TABLE', array_pop($result));
    }
}
