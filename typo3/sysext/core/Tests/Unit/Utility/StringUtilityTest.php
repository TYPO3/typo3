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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\StringUtility
 */
class StringUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getUniqueIdReturnsIdWithPrefix(): void
    {
        $id = StringUtility::getUniqueId('NEW');
        self::assertEquals('NEW', substr($id, 0, 3));
    }

    /**
     * @test
     */
    public function getUniqueIdReturnsIdWithoutDot(): void
    {
        self::assertStringNotContainsString('.', StringUtility::getUniqueId());
    }

    /**
     * @test
     * @param string $selector
     * @param string $expectedValue
     * @dataProvider escapeCssSelectorDataProvider
     */
    public function escapeCssSelector(string $selector, string $expectedValue): void
    {
        self::assertEquals($expectedValue, StringUtility::escapeCssSelector($selector));
    }

    /**
     * @return array
     */
    public function escapeCssSelectorDataProvider(): array
    {
        return [
            ['data.field', 'data\\.field'],
            ['#theId', '\\#theId'],
            ['.theId:hover', '\\.theId\\:hover'],
            ['.theId:hover', '\\.theId\\:hover'],
            ['input[name=foo]', 'input\\[name\\=foo\\]'],
        ];
    }

    /**
     * @param string $input
     * @param string $expectedValue
     * @test
     * @dataProvider removeByteOrderMarkDataProvider
     */
    public function removeByteOrderMark(string $input, string $expectedValue): void
    {
        // assertContains is necessary as one test contains non-string characters
        self::assertSame($expectedValue, StringUtility::removeByteOrderMark(hex2bin($input)));
    }

    /**
     * @return array
     */
    public function removeByteOrderMarkDataProvider(): array
    {
        return [
            'BOM gets removed' => [
                'efbbbf424f4d2061742074686520626567696e6e696e6720676574732072656d6f766564',
                'BOM at the beginning gets removed'
            ],
            'No BOM available' => [
                '4e6f20424f4d20617661696c61626c65',
                'No BOM available',
            ],
        ];
    }

    /**
     * @param $haystack
     * @param $needle
     * @param $result
     * @test
     * @dataProvider searchStringWildcardDataProvider
     */
    public function searchStringWildcard($haystack, $needle, $result): void
    {
        self::assertSame($result, StringUtility::searchStringWildcard($haystack, $needle));
    }

    /**
     * @return array
     */
    public function searchStringWildcardDataProvider(): array
    {
        return [
            'Simple wildcard single character with *' => [
                'TYPO3',
                'TY*O3',
                true
            ],
            'Simple wildcard multiple character with *' => [
                'TYPO3',
                'T*P*3',
                true
            ],
            'Simple wildcard multiple character for one placeholder with *' => [
                'TYPO3',
                'T*3',
                true
            ],
            'Simple wildcard single character with ?' => [
                'TYPO3',
                'TY?O3',
                true
            ],
            'Simple wildcard multiple character with ?' => [
                'TYPO3',
                'T?P?3',
                true
            ],
            'Simple wildcard multiple character for one placeholder with ?' => [
                'TYPO3',
                'T?3',
                false
            ],
            'RegExp' => [
                'TYPO3',
                '/^TYPO(\d)$/',
                true
            ],
        ];
    }

    /**
     * Data provider for uniqueListUnifiesCommaSeparatedList
     *
     * @return \Generator
     */
    public function uniqueListUnifiesCommaSeparatedListDataProvider(): \Generator
    {
        yield 'List without duplicates' => ['one,two,three', 'one,two,three'];
        yield 'List with two consecutive duplicates' => ['one,two,two,three,three', 'one,two,three'];
        yield 'List with non-consecutive duplicates' => ['one,two,three,two,three', 'one,two,three'];
        yield 'One item list' => ['one', 'one'];
        yield 'Empty list' => ['', ''];
        yield 'No list, just a comma' => [',', ''];
        yield 'List with leading comma' => [',one,two', 'one,two'];
        yield 'List with trailing comma' => ['one,two,', 'one,two'];
        yield 'List with multiple consecutive commas' => ['one,,two', 'one,two'];
    }

    /**
     * @test
     *
     * @param string $initialList
     * @param string $unifiedList
     *
     * @dataProvider uniqueListUnifiesCommaSeparatedListDataProvider
     */
    public function uniqueListUnifiesCommaSeparatedList(string $initialList, string $unifiedList): void
    {
        self::assertSame($unifiedList, StringUtility::uniqueList($initialList));
    }
}
