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

namespace TYPO3\CMS\Core\Tests\Functional\Schema;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\RecordTypeResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordTypeResolverTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordTypeResolver.csv');
    }

    public static function typeIsResolvedDataProvider(): \Generator
    {
        yield 'type field of the record' => ['tt_content', ['CType' => 'text'], 'text', 'text', 'text'];
        yield 'type without sub schema' => ['pages', ['doktype' => 12345], '12345', null, '1'];
        yield 'missing type field' => ['pages', ['uid' => 1], null, null, '1'];
        yield 'type field with one value' => ['tt_content', ['CType' => ['text']], 'text', 'text', 'text'];
        yield 'type field with multiple values' => ['pages', ['doktype' => ['1', '254']], null, null, '1'];
        yield 'table without types' => ['be_groups', ['uid' => 1], null, null, '1'];
        yield 'unknown table' => ['tx_unknown', ['uid' => 1], null, null, '1'];
        yield 'type of related record' => ['sys_file_metadata', ['uid' => 1, 'file' => 1], '2', null, '1'];
        yield 'type of related record with pointer from database' => ['sys_file_metadata', ['uid' => 1], '2', null, '1'];
        yield 'type of missing related record' => ['sys_file_metadata', ['uid' => 1, 'file' => 99], null, null, '1'];
        yield 'empty pointer to related record' => ['sys_file_metadata', ['uid' => 1, 'file' => 0], null, null, '1'];
    }

    #[DataProvider('typeIsResolvedDataProvider')]
    #[Test]
    public function typeIsResolved(string $table, array $row, ?string $expectedTypeValue, ?string $expectedRecordType, string $expectedRecordTypeWithFallback): void
    {
        $subject = $this->get(RecordTypeResolver::class);
        self::assertSame($expectedTypeValue, $subject->getTypeValue($table, $row));
        self::assertSame($expectedRecordType, $subject->getRecordType($table, $row));
        self::assertSame($expectedRecordTypeWithFallback, $subject->getRecordTypeWithFallback($table, $row));
    }

    #[Test]
    public function typeOfRelatedRecordSelectsSubSchema(): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_file')->update('sys_file', ['type' => 1], ['uid' => 1]);
        self::assertSame('1', $this->get(RecordTypeResolver::class)->getRecordType('sys_file_metadata', ['uid' => 1, 'file' => 1]));
    }
}
