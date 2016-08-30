<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

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

use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\StringUtility
 */
class StringUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Data provider for isLastPartOfStrReturnsTrueForMatchingLastParts
     *
     * @return array
     */
    public function isLastPartOfStringReturnsTrueForMatchingFirstPartDataProvider()
    {
        return [
            'match last part of string' => ['hello world', 'world'],
            'match last char of string' => ['hellod world', 'd'],
            'match whole string' => ['hello', 'hello'],
            'integer is part of string with same number' => ['24', 24],
            'string is part of integer with same number' => [24, '24'],
            'integer is part of string starting with same number' => ['please gimme beer, 24', 24]
        ];
    }

    /**
     * @test
     * @dataProvider isLastPartOfStringReturnsTrueForMatchingFirstPartDataProvider
     */
    public function isLastPartOfStringReturnsTrueForMatchingFirstPart($string, $part)
    {
        $this->assertTrue(\TYPO3\CMS\Core\Utility\StringUtility::isLastPartOfString($string, $part));
    }

    /**
     * Data provider for checkisLastPartOfStringReturnsFalseForNotMatchingFirstParts
     *
     * @return array
     */
    public function isLastPartOfStringReturnsFalseForNotMatchingFirstPartDataProvider()
    {
        return [
            'no string match' => ['hello', 'bye'],
            'no case sensitive string match' => ['hello world', 'World'],
        ];
    }

    /**
     * @test
     * @dataProvider isLastPartOfStringReturnsFalseForNotMatchingFirstPartDataProvider
     */
    public function isLastPartOfStringReturnsFalseForNotMatchingFirstPart($string, $part)
    {
        $this->assertFalse(\TYPO3\CMS\Core\Utility\StringUtility::isLastPartOfString($string, $part));
    }

    /**
     * Data provider for isLastPartOfStringReturnsThrowsExceptionWithInvalidArguments
     *
     * @return array
     */
    public function isLastPartOfStringReturnsInvalidArgumentDataProvider()
    {
        return [
            'array is not part of string' => ['string', []],
            'string is not part of array' => [[], 'string'],
            'NULL is not part of string' => ['string', null],
            'null is not part of array' => [null, 'string'],
            'NULL is not part of array' => [[], null],
            'array is not part of null' => [null, []],
            'NULL is not part of empty string' => ['', null],
            'false is not part of empty string' => ['', false],
            'empty string is not part of NULL' => [null, ''],
            'empty string is not part of false' => [false, ''],
            'empty string is not part of zero integer' => [0, ''],
            'zero integer is not part of NULL' => [null, 0],
            'zero integer is not part of empty string' => ['', 0],
            'string is not part of object' => [new \stdClass(), 'foo'],
            'object is not part of string' => ['foo', new \stdClass()],
        ];
    }

    /**
     * @test
     * @dataProvider isLastPartOfStringReturnsInvalidArgumentDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function isLastPartOfStringReturnsThrowsExceptionWithInvalidArguments($string, $part)
    {
        $this->assertFalse(\TYPO3\CMS\Core\Utility\StringUtility::isLastPartOfString($string, $part));
    }

    /**
     * Data provider for endsWithReturnsTrueForMatchingFirstPart
     *
     * @return array
     */
    public function endsWithReturnsTrueForMatchingLastPartDataProvider()
    {
        return [
            'match last part of string' => ['hello world', 'world'],
            'match last char of string' => ['hellod world', 'd'],
            'match whole string' => ['hello', 'hello'],
            'integer is part of string with same number' => ['24', 24],
            'string is part of integer with same number' => [24, '24'],
            'integer is part of string ending with same number' => ['please gimme beer, 24', 24]
        ];
    }

    /**
     * @test
     * @dataProvider endsWithReturnsTrueForMatchingLastPartDataProvider
     */
    public function endsWithReturnsTrueForMatchingLastPart($string, $part)
    {
        $this->assertTrue(StringUtility::endsWith($string, $part));
    }

    /**
     * Data provider for check endsWithReturnsFalseForNotMatchingLastPart
     *
     * @return array
     */
    public function endsWithReturnsFalseForNotMatchingLastPartDataProvider()
    {
        return [
            'no string match' => ['hello', 'bye'],
            'no case sensitive string match' => ['hello world', 'World'],
            'string is part but not last part' => ['hello world', 'worl'],
            'integer is not part of empty string' => ['', 0],
            'longer string is not part of shorter string' => ['a', 'aa'],
        ];
    }

    /**
     * @test
     * @dataProvider endsWithReturnsFalseForNotMatchingLastPartDataProvider
     */
    public function endsWithReturnsFalseForNotMatchingLastPart($string, $part)
    {
        $this->assertFalse(StringUtility::endsWith($string, $part));
    }

    /**
     * Data provider for endsWithReturnsThrowsExceptionWithInvalidArguments
     *
     * @return array
     */
    public function endsWithReturnsThrowsExceptionWithInvalidArgumentsDataProvider()
    {
        return [
            'array is not part of string' => ['string', []],
            'NULL is not part of string' => ['string', null],
            'empty string is not part of string' => ['string', ''],
            'string is not part of array' => [[], 'string'],
            'NULL is not part of array' => [[], null],
            'string is not part of NULL' => [null, 'string'],
            'array is not part of NULL' => [null, []],
            'integer is not part of NULL' => [null, 0],
            'empty string is not part of NULL' => [null, ''],
            'NULL is not part of empty string' => ['', null],
            'FALSE is not part of empty string' => ['', false],
            'empty string is not part of FALSE' => [false, ''],
            'empty string is not part of integer' => [0, ''],
            'string is not part of object' => [new \stdClass(), 'foo'],
            'object is not part of string' => ['foo', new \stdClass()],
        ];
    }

    /**
     * @test
     * @dataProvider endsWithReturnsThrowsExceptionWithInvalidArgumentsDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function endsWithReturnsThrowsExceptionWithInvalidArguments($string, $part)
    {
        StringUtility::endsWith($string, $part);
    }

    /**
     * Data provider for beginsWithReturnsTrueForMatchingFirstPart
     *
     * @return array
     */
    public function beginsWithReturnsTrueForMatchingFirstPartDataProvider()
    {
        return [
            'match first part of string' => ['hello world', 'hello'],
            'match first char of string' => ['hello world', 'h'],
            'match whole string' => ['hello', 'hello'],
            'integer is part of string with same number' => ['24', 24],
            'string is part of integer with same number' => [24, '24'],
            'integer is part of string starting with same number' => ['24, please gimme beer', 24],
        ];
    }

    /**
     * @test
     * @dataProvider beginsWithReturnsTrueForMatchingFirstPartDataProvider
     */
    public function beginsWithReturnsTrueForMatchingFirstPart($string, $part)
    {
        $this->assertTrue(StringUtility::beginsWith($string, $part));
    }

    /**
     * Data provider for check beginsWithReturnsFalseForNotMatchingFirstPart
     *
     * @return array
     */
    public function beginsWithReturnsFalseForNotMatchingFirstPartDataProvider()
    {
        return [
            'no string match' => ['hello', 'bye'],
            'no case sensitive string match' => ['hello world', 'Hello'],
            'string in empty string' => ['', 'foo']
        ];
    }

    /**
     * @test
     * @dataProvider beginsWithReturnsFalseForNotMatchingFirstPartDataProvider
     */
    public function beginsWithReturnsFalseForNotMatchingFirstPart($string, $part)
    {
        $this->assertFalse(StringUtility::beginsWith($string, $part));
    }

    /**
     * Data provider for beginsWithReturnsThrowsExceptionWithInvalidArguments
     *
     * @return array
     */
    public function beginsWithReturnsInvalidArgumentDataProvider()
    {
        return [
            'array is not part of string' => ['string', []],
            'NULL is not part of string' => ['string', null],
            'empty string is not part of string' => ['string', ''],
            'string is not part of array' => [[], 'string'],
            'NULL is not part of array' => [[], null],
            'string is not part of NULL' => [null, 'string'],
            'array is not part of NULL' => [null, []],
            'integer is not part of NULL' => [null, 0],
            'empty string is not part of NULL' => [null, ''],
            'NULL is not part of empty string' => ['', null],
            'FALSE is not part of empty string' => ['', false],
            'empty string is not part of FALSE' => [false, ''],
            'empty string is not part of integer' => [0, ''],
            'string is not part of object' => [new \stdClass(), 'foo'],
            'object is not part of string' => ['foo', new \stdClass()],
        ];
    }

    /**
     * @test
     * @dataProvider beginsWithReturnsInvalidArgumentDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function beginsWithReturnsThrowsExceptionWithInvalidArguments($string, $part)
    {
        StringUtility::beginsWith($string, $part);
    }

    /**
     * @test
     */
    public function getUniqueIdReturnsIdWithPrefix()
    {
        $id = StringUtility::getUniqueId('NEW');
        $this->assertEquals('NEW', substr($id, 0, 3));
    }

    /**
     * @test
     */
    public function getUniqueIdReturnsIdWithoutDot()
    {
        $this->assertNotContains('.', StringUtility::getUniqueId());
    }
}
