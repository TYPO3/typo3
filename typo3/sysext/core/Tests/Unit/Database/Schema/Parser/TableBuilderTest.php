<?php
declare(strict_types = 1);

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

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\TextType;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for TableBuilder
 */
class TableBuilderTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var Table
     */
    protected $table;

    /**
     * Setup test subject
     */
    protected function setUp()
    {
        parent::setUp();
        $sqlFile = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Fixtures', 'tablebuilder.sql']));
        $signalSlotDispatcherProphecy = $this->prophesize(Dispatcher::class);
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $sqlReader = new SqlReader($signalSlotDispatcherProphecy->reveal(), $packageManagerProphecy->reveal());
        $statements = $sqlReader->getCreateTableStatementArray($sqlFile);

        $parser = new Parser($statements[0]);
        $this->table = $parser->parse()[0];
    }

    /**
     * @test
     */
    public function hasExpectedTableName()
    {
        $this->assertSame('aTestTable', $this->table->getName());
    }

    /**
     * @test
     */
    public function hasExpectedTableEngine()
    {
        $this->assertTrue($this->table->hasOption('engine'));
        $this->assertSame('MyISAM', $this->table->getOption('engine'));
    }

    /**
     * @test
     */
    public function hasExpectedTableCollation()
    {
        $this->assertTrue($this->table->hasOption('charset'));
        $this->assertSame('latin1', $this->table->getOption('charset'));
    }

    /**
     * @test
     */
    public function hasExpectedTableCharacterSet()
    {
        $this->assertTrue($this->table->hasOption('collate'));
        $this->assertSame('latin1_german_cs', $this->table->getOption('collate'));
    }

    /**
     * @test
     */
    public function hasExpectedTableRowFormat()
    {
        $this->assertTrue($this->table->hasOption('row_format'));
        $this->assertSame('DYNAMIC', $this->table->getOption('row_format'));
    }

    /**
     * @test
     */
    public function hasExpectedTableAutoIncrementValue()
    {
        $this->assertTrue($this->table->hasOption('auto_increment'));
        $this->assertSame('1', $this->table->getOption('auto_increment'));
    }

    /**
     * @test
     */
    public function isExpectedUidColumn()
    {
        $subject = $this->table->getColumn('uid');
        $this->assertInstanceOf(IntegerType::class, $subject->getType());
        $this->assertSame(11, $subject->getLength());
        $this->assertFalse($subject->getUnsigned());
        $this->assertTrue($subject->getNotnull());
        $this->assertNull($subject->getDefault());
        $this->assertTrue($subject->getAutoincrement());
    }

    /**
     * @test
     */
    public function isExpectedPidColumn()
    {
        $subject = $this->table->getColumn('pid');
        $this->assertInstanceOf(IntegerType::class, $subject->getType());
        $this->assertSame(11, $subject->getLength());
        $this->assertFalse($subject->getUnsigned());
        $this->assertTrue($subject->getNotnull());
        $this->assertFalse($subject->getAutoincrement());
        $this->assertSame('0', $subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedTstampColumn()
    {
        $subject = $this->table->getColumn('tstamp');
        $this->assertInstanceOf(IntegerType::class, $subject->getType());
        $this->assertSame(11, $subject->getLength());
        $this->assertTrue($subject->getUnsigned());
        $this->assertTrue($subject->getNotnull());
        $this->assertFalse($subject->getAutoincrement());
        $this->assertSame('0', $subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedSortingColumn()
    {
        $subject = $this->table->getColumn('sorting');
        $this->assertInstanceOf(IntegerType::class, $subject->getType());
        $this->assertSame(11, $subject->getLength());
        $this->assertTrue($subject->getUnsigned());
        $this->assertTrue($subject->getNotnull());
        $this->assertFalse($subject->getAutoincrement());
        $this->assertSame(0, $subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedDeletedColumn()
    {
        $subject = $this->table->getColumn('deleted');
        $this->assertInstanceOf(SmallIntType::class, $subject->getType());
        $this->assertSame(1, $subject->getLength());
        $this->assertTrue($subject->getUnsigned());
        $this->assertTrue($subject->getNotnull());
        $this->assertFalse($subject->getAutoincrement());
        $this->assertSame('0', $subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedTSconfigColumn()
    {
        $subject = $this->table->getColumn('TSconfig');
        $this->assertInstanceOf(TextType::class, $subject->getType());
        $this->assertSame(65535, $subject->getLength());
        $this->assertFalse($subject->getNotnull());
        $this->assertNull($subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedNoCacheColumn()
    {
        $subject = $this->table->getColumn('no_cache');
        $this->assertInstanceOf(IntegerType::class, $subject->getType());
        $this->assertSame(10, $subject->getLength());
        $this->assertTrue($subject->getUnsigned());
        $this->assertTrue($subject->getNotnull());
        $this->assertFalse($subject->getAutoincrement());
        $this->assertSame('0', $subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedPrimaryKey()
    {
        $subject = $this->table->getPrimaryKey();
        $this->assertInstanceOf(Index::class, $subject);
        $this->assertTrue($subject->isPrimary());
        $this->assertSame(['`uid`'], $subject->getColumns());
    }

    /**
     * @test
     */
    public function isExpectedParentKey()
    {
        $subject = $this->table->getIndex('parent');
        $this->assertInstanceOf(Index::class, $subject);
        $this->assertTrue($subject->isUnique());
        $this->assertSame(['`pid`', '`deleted`', '`sorting`'], $subject->getColumns());
    }

    /**
     * @test
     */
    public function isExpectedNoCacheKey()
    {
        $subject = $this->table->getIndex('noCache');
        $this->assertInstanceOf(Index::class, $subject);
        $this->assertTrue($subject->isSimpleIndex());
        $this->assertSame(['`no_cache`'], $subject->getColumns());
    }

    /**
     * @test
     */
    public function isExpectedForeignKey()
    {
        $subject = $this->table->getForeignKey('fk_overlay');
        $this->assertInstanceOf(ForeignKeyConstraint::class, $subject);
        $this->assertSame(['`pid`'], $subject->getForeignColumns());
        $this->assertSame(['`uid`'], $subject->getLocalColumns());
        $this->assertSame('aTestTable', $subject->getLocalTableName());
        $this->assertSame('any_foreign_table', $subject->getForeignTableName());
    }

    /**
     * @test
     */
    public function hasColumnLengthOnIndex()
    {
        $subject = $this->table->getIndex('substring');
        $this->assertSame(['`TSconfig`(80)'], $subject->getColumns());
    }
}
