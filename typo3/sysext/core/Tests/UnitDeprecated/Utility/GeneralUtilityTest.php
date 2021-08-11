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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class GeneralUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function compileSelectedGetVarsFromArrayFiltersIncomingData(): void
    {
        $filter = 'foo,bar';
        $getArray = ['foo' => 1, 'cake' => 'lie'];
        $expected = ['foo' => 1];
        $result = GeneralUtility::compileSelectedGetVarsFromArray($filter, $getArray, false);
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function compileSelectedGetVarsFromArrayUsesGetPostDataFallback(): void
    {
        $_GET['bar'] = '2';
        $filter = 'foo,bar';
        $getArray = ['foo' => 1, 'cake' => 'lie'];
        $expected = ['foo' => 1, 'bar' => '2'];
        $result = GeneralUtility::compileSelectedGetVarsFromArray($filter, $getArray, true);
        self::assertSame($expected, $result);
    }

    /**
     * @test
     * @dataProvider rmFromListRemovesElementsFromCommaSeparatedListDataProvider
     *
     * @param string $initialList
     * @param string $listWithElementRemoved
     */
    public function rmFromListRemovesElementsFromCommaSeparatedList(
        string $initialList,
        string $listWithElementRemoved
    ): void {
        self::assertSame($listWithElementRemoved, GeneralUtility::rmFromList('removeme', $initialList));
    }

    /**
     * Data provider for rmFromListRemovesElementsFromCommaSeparatedList
     *
     * @return array
     */
    public function rmFromListRemovesElementsFromCommaSeparatedListDataProvider(): array
    {
        return [
            'Element as second element of three' => ['one,removeme,two', 'one,two'],
            'Element at beginning of list' => ['removeme,one,two', 'one,two'],
            'Element at end of list' => ['one,two,removeme', 'one,two'],
            'One item list' => ['removeme', ''],
            'Element not contained in list' => ['one,two,three', 'one,two,three'],
            'Empty element survives' => ['one,,three,,removeme', 'one,,three,'],
            'Empty element survives at start' => [',removeme,three,removeme', ',three'],
            'Empty element survives at end' => ['removeme,three,removeme,', 'three,'],
            'Empty list' => ['', ''],
            'List contains removeme multiple times' => ['removeme,notme,removeme,removeme', 'notme'],
            'List contains removeme multiple times nothing else' => ['removeme,removeme,removeme', ''],
            'List contains removeme multiple times nothing else 2x' => ['removeme,removeme', ''],
            'List contains removeme multiple times nothing else 3x' => ['removeme,removeme,removeme', ''],
            'List contains removeme multiple times nothing else 4x' => ['removeme,removeme,removeme,removeme', ''],
            'List contains removeme multiple times nothing else 5x' => ['removeme,removeme,removeme,removeme,removeme', ''],
        ];
    }

    ///////////////////////////////
    // Tests concerning isFirstPartOfStr
    ///////////////////////////////
    /**
     * Data provider for isFirstPartOfStrReturnsTrueForMatchingFirstParts
     *
     * @return array
     */
    public function isFirstPartOfStrReturnsTrueForMatchingFirstPartDataProvider(): array
    {
        return [
            'match first part of string' => ['hello world', 'hello'],
            'match whole string' => ['hello', 'hello'],
            'integer is part of string with same number' => ['24', 24],
            'string is part of integer with same number' => [24, '24'],
            'integer is part of string starting with same number' => ['24 beer please', 24]
        ];
    }

    /**
     * @test
     * @dataProvider isFirstPartOfStrReturnsTrueForMatchingFirstPartDataProvider
     */
    public function isFirstPartOfStrReturnsTrueForMatchingFirstPart($string, $part): void
    {
        self::assertTrue(GeneralUtility::isFirstPartOfStr($string, $part));
    }

    /**
     * Data provider for checkIsFirstPartOfStrReturnsFalseForNotMatchingFirstParts
     *
     * @return array
     */
    public function isFirstPartOfStrReturnsFalseForNotMatchingFirstPartDataProvider(): array
    {
        return [
            'no string match' => ['hello', 'bye'],
            'no case sensitive string match' => ['hello world', 'Hello'],
            'array is not part of string' => ['string', []],
            'string is not part of array' => [[], 'string'],
            'NULL is not part of string' => ['string', null],
            'string is not part of NULL' => [null, 'string'],
            'NULL is not part of array' => [[], null],
            'array is not part of NULL' => [null, []],
            'empty string is not part of empty string' => ['', ''],
            'NULL is not part of empty string' => ['', null],
            'false is not part of empty string' => ['', false],
            'empty string is not part of NULL' => [null, ''],
            'empty string is not part of false' => [false, ''],
            'empty string is not part of zero integer' => [0, ''],
            'zero integer is not part of NULL' => [null, 0],
            'zero integer is not part of empty string' => ['', 0]
        ];
    }

    /**
     * @test
     * @dataProvider isFirstPartOfStrReturnsFalseForNotMatchingFirstPartDataProvider
     */
    public function isFirstPartOfStrReturnsFalseForNotMatchingFirstPart($string, $part): void
    {
        self::assertFalse(GeneralUtility::isFirstPartOfStr($string, $part));
    }
}
