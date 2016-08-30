<?php
namespace TYPO3\CMS\IndexedSearch\Tests\Unit\Utility;

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
use TYPO3\CMS\IndexedSearch\Utility\LikeWildcard;

/**
 * This class contains unit tests for the LikeQueryUtility
 */
class LikeWildcardTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        /** @var $databaseConnectionMock \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject */
        $databaseConnectionMock = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['quoteStr']);
        $databaseConnectionMock->method('quoteStr')
            ->will($this->returnArgument(0));
        $GLOBALS['TYPO3_DB'] = $databaseConnectionMock;
    }

    /**
     * @test
     * @param string $tableName
     * @param string $fieldName
     * @param string $likeValue
     * @param int $wildcard
     * @param string $expected
     * @dataProvider getLikeQueryPartDataProvider
     */
    public function getLikeQueryPart($tableName, $fieldName, $likeValue, $wildcard, $expected)
    {
        $subject = \TYPO3\CMS\IndexedSearch\Utility\LikeWildcard::cast($wildcard);
        $this->assertSame($expected, $subject->getLikeQueryPart($tableName, $fieldName, $likeValue));
    }

    /**
     * Returns data sets for the test getLikeQueryPart
     * Each dataset is an array with the following elements:
     * - the table name
     * - the field name
     * - the search value
     * - the wildcard mode
     * - the expected result
     *
     * @return array
     */
    public function getLikeQueryPartDataProvider()
    {
        return [
            'no placeholders and no wildcard mode' => [
                'tt_content',
                'body',
                'searchstring',
                LikeWildcard::NONE,
                'body LIKE \'searchstring\''
            ],
            'no placeholders and left wildcard mode' => [
                'tt_content',
                'body',
                'searchstring',
                LikeWildcard::LEFT,
                'body LIKE \'%searchstring\''
            ],
            'no placeholders and right wildcard mode' => [
                'tt_content',
                'body',
                'searchstring',
                LikeWildcard::RIGHT,
                'body LIKE \'searchstring%\''
            ],
            'no placeholders and both wildcards mode' => [
                'tt_content',
                'body',
                'searchstring',
                LikeWildcard::BOTH,
                'body LIKE \'%searchstring%\''
            ],
            'underscore placeholder and left wildcard mode' => [
                'tt_content',
                'body',
                'search_string',
                LikeWildcard::LEFT,
                'body LIKE \'%search\\_string\''
            ],
            'percent placeholder and right wildcard mode' => [
                'tt_content',
                'body',
                'search%string',
                LikeWildcard::RIGHT,
                'body LIKE \'search\\%string%\''
            ],
            'percent and underscore placeholder and both wildcards mode' => [
                'tt_content',
                'body',
                '_search%string_',
                LikeWildcard::RIGHT,
                'body LIKE \'\\_search\\%string\\_%\''
            ],
        ];
    }
}
