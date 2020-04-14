<?php

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

use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\HttpUtility
 */
class HttpUtilityTest extends UnitTestCase
{
    /**
     * @param array $urlParts
     * @param string $expected
     * @dataProvider isUrlBuiltCorrectlyDataProvider
     * @test
     */
    public function isUrlBuiltCorrectly(array $urlParts, $expected)
    {
        $url = HttpUtility::buildUrl($urlParts);
        self::assertEquals($expected, $url);
    }

    /**
     * @return array
     */
    public function isUrlBuiltCorrectlyDataProvider()
    {
        return [
            'rebuild url without scheme' => [
                parse_url('typo3.org/path/index.php'),
                'typo3.org/path/index.php'
            ],
            'rebuild url with scheme' => [
                parse_url('http://typo3.org/path/index.php'),
                'http://typo3.org/path/index.php'
            ],
            'rebuild url with all properties' => [
                parse_url('http://editor:secret@typo3.org:8080/path/index.php?query=data#fragment'),
                'http://editor:secret@typo3.org:8080/path/index.php?query=data#fragment'
            ],
            'url without username, but password' => [
                [
                    'scheme' => 'http',
                    'pass' => 'secrept',
                    'host' => 'typo3.org'
                ],
                'http://typo3.org'
            ]
        ];
    }

    /**
     * Data provider for buildQueryString
     *
     * @return array
     */
    public function queryStringDataProvider()
    {
        $valueArray = ['one' => '√', 'two' => 2];

        return [
            'Empty input' => ['foo', [], ''],
            'String parameters' => ['foo', $valueArray, 'foo%5Bone%5D=%E2%88%9A&foo%5Btwo%5D=2'],
            'Nested array parameters' => ['foo', [$valueArray], 'foo%5B0%5D%5Bone%5D=%E2%88%9A&foo%5B0%5D%5Btwo%5D=2'],
            'Keep blank parameters' => ['foo', ['one' => '√', ''], 'foo%5Bone%5D=%E2%88%9A&foo%5B0%5D=']
        ];
    }

    /**
     * @test
     * @dataProvider queryStringDataProvider
     * @param string $name
     * @param array $input
     * @param string $expected
     */
    public function buildQueryStringBuildsValidParameterString($name, array $input, $expected)
    {
        if ($name === '') {
            self::assertSame($expected, HttpUtility::buildQueryString($input));
        } else {
            self::assertSame($expected, HttpUtility::buildQueryString([$name => $input]));
        }
    }

    /**
     * @test
     */
    public function buildQueryStringCanSkipEmptyParameters()
    {
        $input = ['one' => '√', ''];
        $expected = 'foo%5Bone%5D=%E2%88%9A';
        self::assertSame($expected, HttpUtility::buildQueryString(['foo' => $input], '', true));
    }

    /**
     * @test
     */
    public function buildQueryStringCanUrlEncodeKeyNames()
    {
        $input = ['one' => '√', ''];
        $expected = 'foo%5Bone%5D=%E2%88%9A&foo%5B0%5D=';
        self::assertSame($expected, HttpUtility::buildQueryString(['foo' => $input]));
    }

    /**
     * @test
     */
    public function buildQueryStringCanUrlEncodeKeyNamesMultidimensional()
    {
        $input = ['one' => ['two' => ['three' => '√']], ''];
        $expected = 'foo%5Bone%5D%5Btwo%5D%5Bthree%5D=%E2%88%9A&foo%5B0%5D=';
        self::assertSame($expected, HttpUtility::buildQueryString(['foo' => $input]));
    }

    /**
     * @test
     */
    public function buildQueryStringSkipsLeadingCharacterOnEmptyParameters()
    {
        $input = [];
        $expected = '';
        self::assertSame($expected, HttpUtility::buildQueryString($input, '?', true));
    }

    /**
     * @test
     */
    public function buildQueryStringSkipsLeadingCharacterOnCleanedEmptyParameters()
    {
        $input = ['one' => ''];
        $expected = '';
        self::assertSame($expected, HttpUtility::buildQueryString(['foo' => $input], '?', true));
    }
}
