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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema;

use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SqlReaderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @test
     */
    public function getStatementArraySplitsStatements(): void
    {
        $subject = new SqlReader(new NoopEventDispatcher(), $this->createMock(PackageManager::class));
        $result = $subject->getStatementArray(
            'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) VALUES(1);'
        );
        self::assertCount(2, $result);
        self::assertStringStartsWith('CREATE TABLE', $result[0]);
        self::assertStringStartsWith('INSERT INTO', $result[1]);
    }

    /**
     * @test
     */
    public function getStatementArrayFiltersStatements(): void
    {
        $subject = new SqlReader(new NoopEventDispatcher(), $this->createMock(PackageManager::class));
        $result = $subject->getStatementArray(
            'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) VALUES(1);',
            '^CREATE TABLE'
        );
        self::assertCount(1, $result);
        self::assertStringStartsWith('CREATE TABLE', array_pop($result));
    }

    /**
     * @test
     */
    public function getInsertStatementArrayResult(): void
    {
        $subject = new SqlReader(new NoopEventDispatcher(), $this->createMock(PackageManager::class));
        $result = $subject->getInsertStatementArray(
            'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) VALUES(1);'
        );

        self::assertCount(1, $result);
        self::assertStringStartsWith('INSERT', array_pop($result));
    }

    /**
     * @test
     */
    public function getInsertStatementArrayResultWithNewline(): void
    {
        $subject = new SqlReader(new NoopEventDispatcher(), $this->createMock(PackageManager::class));
        $result = $subject->getInsertStatementArray(
            'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) ' .
            LF .
            'VALUES(1);'
        );

        self::assertCount(1, $result);
        self::assertSame('INSERT INTO aTestTable(`aTestField`) VALUES(1);', array_pop($result));
    }

    /**
     * @test
     */
    public function getCreateTableStatementArrayResult(): void
    {
        $subject = new SqlReader(new NoopEventDispatcher(), $this->createMock(PackageManager::class));
        $result = $subject->getCreateTableStatementArray(
            'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) VALUES(1);'
        );
        self::assertCount(1, $result);
        self::assertStringStartsWith('CREATE TABLE', array_pop($result));
    }

    /**
     * @dataProvider commentProvider
     * @test
     */
    public function getCreateTableStatementArrayResultWithComment(string $comment): void
    {
        $subject = new SqlReader(new NoopEventDispatcher(), $this->createMock(PackageManager::class));
        $result = $subject->getCreateTableStatementArray(
            $comment . LF . 'CREATE TABLE aTestTable(' . LF . '  aTestField INT(11)' . LF . ');' .
            LF .
            'INSERT INTO aTestTable(`aTestField`) VALUES(1);'
        );
        self::assertCount(1, $result);
        self::assertStringStartsWith('CREATE TABLE', array_pop($result));
    }

    public static function commentProvider(): array
    {
        return [
            'Single line comment starting with "#"' => [
                '# Comment',
            ],
            'Single line comment starting with "--"' => [
                '-- Comment',
            ],
            'Single line c-style comment' => [
                '/* Same line c-style comment */',
            ],
            'Multiline comment variant 1' => [
                '/*' . LF . 'Some comment text' . LF . 'more text' . LF . '*/',
            ],
            'Multiline comment variant 2' => [
                '/* More' . LF . ' comments */',
            ],
        ];
    }
}
