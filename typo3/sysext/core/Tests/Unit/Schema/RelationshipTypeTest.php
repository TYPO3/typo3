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

namespace TYPO3\CMS\Core\Tests\Unit\Schema;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\RelationshipType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RelationshipTypeTest extends UnitTestCase
{
    public static function fromTcaConfigurationReturnsCorrectTypeDataProvider(): \Generator
    {
        yield 'no config subarray given' => [
            [],
            RelationshipType::Undefined,
        ];
        yield 'empty config subarray given' => [
            ['config' => []],
            RelationshipType::Undefined,
        ];
        yield 'detect select as static list - this is no relation => undefined' => [
            ['type' => 'select'],
            RelationshipType::Undefined,
        ];
        yield 'use MM if given' => [
            ['type' => 'text', 'config' => ['type' => 'text', 'MM' => 1]],
            RelationshipType::ManyToMany,
        ];
        yield 'detect group in subarraay' => [
            ['type' => 'text', 'config' => ['type' => 'group']],
            RelationshipType::List,
        ];
        yield 'detect group in main level' => [
            ['type' => 'group'],
            RelationshipType::List,
        ];
        yield 'detect select with MM' => [
            ['type' => 'select', 'MM' => true],
            RelationshipType::ManyToMany,
        ];
        yield 'detect select with foreign_table' => [
            ['type' => 'select', 'foreign_table' => 'pages'],
            RelationshipType::List,
        ];
        yield 'detect inline with foreign table' => [
            ['type' => 'inline', 'foreign_table' => 'sys_file_reference'],
            RelationshipType::List,
        ];
        yield 'detect inline with foreign field' => [
            ['type' => 'inline', 'foreign_table' => 'sys_file_reference', 'foreign_field' => 'uid_foreign'],
            RelationshipType::OneToMany,
        ];
        yield 'detect relationship set to "oneToOne"' => [
            ['type' => 'select', 'foreign_table' => 'sys_file_metadata', 'relationship' => 'oneToOne'],
            RelationshipType::OneToOne,
        ];
        yield 'detect relationship set to "manyToOne"' => [
            ['type' => 'group', 'allowed' => 'pages', 'relationship' => 'manyToOne'],
            RelationshipType::ManyToOne,
        ];
        yield 'subarray key overloads main level key' => [
            ['type' => 'group', 'config' => ['type' => 'text']],
            RelationshipType::Undefined,
        ];
    }

    #[DataProvider('fromTcaConfigurationReturnsCorrectTypeDataProvider')]
    #[Test]
    public function fromTcaConfigurationReturnsCorrectType(array $input, RelationshipType $expected): void
    {
        self::assertSame($expected, RelationshipType::fromTcaConfiguration($input));
    }
}
