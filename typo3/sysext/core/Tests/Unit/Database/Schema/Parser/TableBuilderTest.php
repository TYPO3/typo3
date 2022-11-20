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

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\TextType;
use TYPO3\CMS\Core\Database\Schema\Parser\Parser;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TableBuilderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected ?Table $table;

    protected function setUp(): void
    {
        parent::setUp();
        $sqlFile = file_get_contents(__DIR__ . '/../Fixtures/tablebuilder.sql');
        $sqlReader = new SqlReader(new NoopEventDispatcher(), $this->createMock(PackageManager::class));
        $statements = $sqlReader->getCreateTableStatementArray($sqlFile);

        $parser = new Parser($statements[0]);
        $this->table = $parser->parse()[0];
    }

    /**
     * @test
     */
    public function hasExpectedTableName(): void
    {
        self::assertSame('aTestTable', $this->table->getName());
    }

    /**
     * @test
     */
    public function hasExpectedTableEngine(): void
    {
        self::assertTrue($this->table->hasOption('engine'));
        self::assertSame('MyISAM', $this->table->getOption('engine'));
    }

    /**
     * @test
     */
    public function hasExpectedTableCollation(): void
    {
        self::assertTrue($this->table->hasOption('charset'));
        self::assertSame('latin1', $this->table->getOption('charset'));
    }

    /**
     * @test
     */
    public function hasExpectedTableCharacterSet(): void
    {
        self::assertTrue($this->table->hasOption('collate'));
        self::assertSame('latin1_german_cs', $this->table->getOption('collate'));
    }

    /**
     * @test
     */
    public function hasExpectedTableRowFormat(): void
    {
        self::assertTrue($this->table->hasOption('row_format'));
        self::assertSame('DYNAMIC', $this->table->getOption('row_format'));
    }

    /**
     * @test
     */
    public function hasExpectedTableAutoIncrementValue(): void
    {
        self::assertTrue($this->table->hasOption('auto_increment'));
        self::assertSame('1', $this->table->getOption('auto_increment'));
    }

    /**
     * @test
     */
    public function isExpectedUidColumn(): void
    {
        $subject = $this->table->getColumn('uid');
        self::assertInstanceOf(IntegerType::class, $subject->getType());
        self::assertSame(11, $subject->getLength());
        self::assertFalse($subject->getUnsigned());
        self::assertTrue($subject->getNotnull());
        self::assertNull($subject->getDefault());
        self::assertTrue($subject->getAutoincrement());
    }

    /**
     * @test
     */
    public function isExpectedPidColumn(): void
    {
        $subject = $this->table->getColumn('pid');
        self::assertInstanceOf(IntegerType::class, $subject->getType());
        self::assertSame(11, $subject->getLength());
        self::assertFalse($subject->getUnsigned());
        self::assertTrue($subject->getNotnull());
        self::assertFalse($subject->getAutoincrement());
        self::assertSame('0', $subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedTstampColumn(): void
    {
        $subject = $this->table->getColumn('tstamp');
        self::assertInstanceOf(IntegerType::class, $subject->getType());
        self::assertSame(11, $subject->getLength());
        self::assertTrue($subject->getUnsigned());
        self::assertTrue($subject->getNotnull());
        self::assertFalse($subject->getAutoincrement());
        self::assertSame('0', $subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedSortingColumn(): void
    {
        $subject = $this->table->getColumn('sorting');
        self::assertInstanceOf(IntegerType::class, $subject->getType());
        self::assertSame(11, $subject->getLength());
        self::assertTrue($subject->getUnsigned());
        self::assertTrue($subject->getNotnull());
        self::assertFalse($subject->getAutoincrement());
        self::assertSame(0, $subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedDeletedColumn(): void
    {
        $subject = $this->table->getColumn('deleted');
        self::assertInstanceOf(SmallIntType::class, $subject->getType());
        self::assertSame(1, $subject->getLength());
        self::assertTrue($subject->getUnsigned());
        self::assertTrue($subject->getNotnull());
        self::assertFalse($subject->getAutoincrement());
        self::assertSame('0', $subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedTSconfigColumn(): void
    {
        $subject = $this->table->getColumn('TSconfig');
        self::assertInstanceOf(TextType::class, $subject->getType());
        self::assertSame(65535, $subject->getLength());
        self::assertFalse($subject->getNotnull());
        self::assertNull($subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedNoCacheColumn(): void
    {
        $subject = $this->table->getColumn('no_cache');
        self::assertInstanceOf(IntegerType::class, $subject->getType());
        self::assertSame(10, $subject->getLength());
        self::assertTrue($subject->getUnsigned());
        self::assertTrue($subject->getNotnull());
        self::assertFalse($subject->getAutoincrement());
        self::assertSame('0', $subject->getDefault());
    }

    /**
     * @test
     */
    public function isExpectedPrimaryKey(): void
    {
        $subject = $this->table->getPrimaryKey();
        self::assertInstanceOf(Index::class, $subject);
        self::assertTrue($subject->isPrimary());
        self::assertSame(['`uid`'], $subject->getColumns());
    }

    /**
     * @test
     */
    public function isExpectedParentKey(): void
    {
        $subject = $this->table->getIndex('parent');
        self::assertInstanceOf(Index::class, $subject);
        self::assertTrue($subject->isUnique());
        self::assertSame(['`pid`', '`deleted`', '`sorting`'], $subject->getColumns());
    }

    /**
     * @test
     */
    public function isExpectedNoCacheKey(): void
    {
        $subject = $this->table->getIndex('noCache');
        self::assertInstanceOf(Index::class, $subject);
        self::assertTrue($subject->isSimpleIndex());
        self::assertSame(['`no_cache`'], $subject->getColumns());
    }

    /**
     * @test
     */
    public function isExpectedForeignKey(): void
    {
        $subject = $this->table->getForeignKey('fk_overlay');
        self::assertInstanceOf(ForeignKeyConstraint::class, $subject);
        self::assertSame(['`pid`'], $subject->getForeignColumns());
        self::assertSame(['`uid`'], $subject->getLocalColumns());
        self::assertSame('aTestTable', $subject->getLocalTableName());
        self::assertSame('any_foreign_table', $subject->getForeignTableName());
    }

    /**
     * @test
     */
    public function hasColumnLengthOnIndex(): void
    {
        $subject = $this->table->getIndex('substring');
        self::assertSame(['`TSconfig`(80)'], $subject->getColumns());
    }
}
