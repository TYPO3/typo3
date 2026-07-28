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

namespace TYPO3\CMS\Core\Tests\Unit\Schema\Field;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\Field\GroupFieldType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GroupFieldTypeTest extends UnitTestCase
{
    public static function getAllowedSchemaNamesDataProvider(): \Generator
    {
        yield 'no allowed configuration' => [
            ['type' => 'group'],
            [],
        ];
        yield 'empty allowed configuration' => [
            ['type' => 'group', 'allowed' => ''],
            [],
        ];
        yield 'single schema' => [
            ['type' => 'group', 'allowed' => 'tt_content'],
            ['tt_content'],
        ];
        yield 'multiple schemata' => [
            ['type' => 'group', 'allowed' => 'tt_content,pages'],
            ['tt_content', 'pages'],
        ];
        yield 'multiple schemata with whitespace and empty values' => [
            ['type' => 'group', 'allowed' => ' tt_content , , pages '],
            ['tt_content', 'pages'],
        ];
        yield 'wildcard is kept as list item' => [
            ['type' => 'group', 'allowed' => '*'],
            ['*'],
        ];
    }

    #[DataProvider('getAllowedSchemaNamesDataProvider')]
    #[Test]
    public function getAllowedSchemaNamesReturnsExpectedList(array $configuration, array $expected): void
    {
        $fieldType = new GroupFieldType('test', $configuration, []);
        self::assertSame($expected, $fieldType->getAllowedSchemaNames());
    }

    public static function allowsAllSchemataDataProvider(): \Generator
    {
        yield 'no allowed configuration' => [
            ['type' => 'group'],
            false,
        ];
        yield 'single schema' => [
            ['type' => 'group', 'allowed' => 'tt_content'],
            false,
        ];
        yield 'wildcard' => [
            ['type' => 'group', 'allowed' => '*'],
            true,
        ];
        yield 'wildcard combined with a schema' => [
            ['type' => 'group', 'allowed' => 'tt_content,*'],
            true,
        ];
    }

    #[DataProvider('allowsAllSchemataDataProvider')]
    #[Test]
    public function allowsAllSchemataReturnsExpectedResult(array $configuration, bool $expected): void
    {
        $fieldType = new GroupFieldType('test', $configuration, []);
        self::assertSame($expected, $fieldType->allowsAllSchemata());
    }
}
