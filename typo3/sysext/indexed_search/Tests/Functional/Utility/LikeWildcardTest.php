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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional\Utility;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\IndexedSearch\Utility\LikeWildcard;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * This class contains unit tests for the LikeQueryUtility
 */
class LikeWildcardTest extends FunctionalTestCase
{
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
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
        $subject = LikeWildcard::cast($wildcard);
        // MySQL has support for backslash escape sequences, the expected results needs to take
        // the additional quoting into account.
        if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $expected = addcslashes($expected, '\\');
        }
        $expected = $connection->quoteIdentifier($fieldName) . ' ' . $expected;
        self::assertSame($expected, $subject->getLikeQueryPart($tableName, $fieldName, $likeValue));
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
                "LIKE 'searchstring'"
            ],
            'no placeholders and left wildcard mode' => [
                'tt_content',
                'body',
                'searchstring',
                LikeWildcard::LEFT,
                "LIKE '%searchstring'"
            ],
            'no placeholders and right wildcard mode' => [
                'tt_content',
                'body',
                'searchstring',
                LikeWildcard::RIGHT,
                "LIKE 'searchstring%'"
            ],
            'no placeholders and both wildcards mode' => [
                'tt_content',
                'body',
                'searchstring',
                LikeWildcard::BOTH,
                "LIKE '%searchstring%'"
            ],
            'underscore placeholder and left wildcard mode' => [
                'tt_content',
                'body',
                'search_string',
                LikeWildcard::LEFT,
                "LIKE '%search\\_string'"
            ],
            'percent placeholder and right wildcard mode' => [
                'tt_content',
                'body',
                'search%string',
                LikeWildcard::RIGHT,
                "LIKE 'search\\%string%'"
            ],
            'percent and underscore placeholder and both wildcards mode' => [
                'tt_content',
                'body',
                '_search%string_',
                LikeWildcard::RIGHT,
                "LIKE '\\_search\\%string\\_%'"
            ],
        ];
    }
}
