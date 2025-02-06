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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Query\Expression\ExpressionBuilder;

use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LikeTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_expressionbuilder',
    ];

    public static function likeReturnsExpectedDataSetsDataProvider(): \Generator
    {
        yield 'lowercase search word matches german umlauts in upper and lower casing #1' => [
            'section' => 'likecasing',
            'searchWord' => '%über%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 1,
                    'aCsvField' => 'Fächer, Überraschungen sind Äußerungen',
                ],
                1 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [
                // Exclude sqlite due to german umlauts.
                DoctrineSQLitePlatform::class,
            ],
        ];
        yield 'lowercase search word matches german umlauts in upper and lower casing #2' => [
            'section' => 'likecasing',
            'searchWord' => '%ältere%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [
                // Exclude sqlite due to german umlauts.
                DoctrineSQLitePlatform::class,
            ],
        ];
        yield 'uppercase search word matches german umlauts in upper and lower casing #1' => [
            'section' => 'likecasing',
            'searchWord' => '%Ältere%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [
                // Exclude sqlite due to german umlauts.
                DoctrineSQLitePlatform::class,
            ],
        ];
        yield 'lowercase ascii search word matches properly case-insensitive' => [
            'section' => 'likecasing',
            'searchWord' => '%klein%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'uppercase ascii search word matches properly case-insensitive' => [
            'section' => 'likecasing',
            'searchWord' => '%KLEIN%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'escaped value with underscore matches properly' => [
            'section' => 'likeescape',
            // addcslashes() is used in escapeLikeWildcards()
            'searchWord' => '%' . addcslashes('underscore_escape_can_be_matched', '%_') . '%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 3,
                    'aCsvField' => 'underscore_escape_can_be_matched,second_value',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'escaped value with % matches properly' => [
            'section' => 'likeescape',
            // addcslashes() is used in escapeLikeWildcards()
            'searchWord' => '%' . addcslashes('a % in', '%_') . '%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 5,
                    'aCsvField' => 'Some value with a % in it',
                ],
            ],
            'excludePlatforms' => [],
        ];
        // @todo Prepared test cases for case-sensitive like enforcement (not guaranteed yet), but required to ensure
        //       https://docs.typo3.org/m/typo3/reference-tca/11.5/en-us/ColumnsConfig/CommonProperties/Search.html#confval-case
        yield 'Case-sensitive for "Some"' => [
            'section' => 'casesensitive',
            'searchWord' => '%Some%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 6,
                    'aCsvField' => 'Some text with to some in different casing.',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'Case-sensitive for "some"' => [
            'section' => 'casesensitive',
            'searchWord' => '%some%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 6,
                    'aCsvField' => 'Some text with to some in different casing.',
                ],
                [
                    'uid' => 7,
                    'aCsvField' => 'Another text with only lowercased some in it.',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'Case-sensitive for "SOME"' => [
            'section' => 'casesensitive',
            'searchWord' => '%SOME%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 8,
                    'aCsvField' => 'Another text with only one uppercased SOME in it.',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'Case-sensitive for "SoMe"' => [
            'section' => 'casesensitive',
            'searchWord' => '%SoMe%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 9,
                    'aCsvField' => 'Text with mixed case SoMe in it.',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'Case-sensitive with german umlaut "ÜBERRASCHUNG"' => [
            'section' => 'casesensitive-umlaut',
            'searchWord' => '%ÜBERRASCHUNG%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 10,
                    'aCsvField' => 'ÜBERRASCHUNG: Für manche ist es jede Überraschung ein Schock.',
                ],
                [
                    'uid' => 11,
                    'aCsvField' => 'Stay tuned, ÜBERRASCHUNG steht in den Starlöchern.',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'Case-sensitive with german umlaut "Überraschung"' => [
            'section' => 'casesensitive-umlaut',
            'searchWord' => '%Überraschung%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 10,
                    'aCsvField' => 'ÜBERRASCHUNG: Für manche ist es jede Überraschung ein Schock.',
                ],
                [
                    'uid' => 12,
                    'aCsvField' => 'Ein Lottogewinn ist schon eine starke Überraschung.',
                ],
            ],
            'excludePlatforms' => [],
        ];
    }

    #[DataProvider('likeReturnsExpectedDataSetsDataProvider')]
    #[Test]
    /**
     * Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
     *       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
     *       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt')]
     */
    public function likeReturnsExpectedDataSetsForTextFields(string $section, string $searchWord, bool $caseSensitive, array $expectedRows, array $excludePlatforms): void
    {
        if ($caseSensitive === true) {
            // Skip case-sensitive test mutations, not implemented yet. Added as preparation to test different
            // approaches without having to clone tests in all test variants / changes.
            // @todo Remove when case-sensitive like() has been implemented.
            self::markTestSkipped('Case-sensitive like not implemented yet.');
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LikeAndNotLike_TEXT.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        if ($excludePlatforms !== []) {
            $platform = $queryBuilder->getConnection()->getDatabasePlatform();
            foreach ($excludePlatforms as $excludePlatform) {
                if ($platform instanceof $excludePlatform) {
                    self::markTestSkipped('Excluded platform ' . $excludePlatform);
                }
            }
        }
        $rows = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                // narrow down result set
                $queryBuilder->expr()->eq('aField', $queryBuilder->createNamedParameter($section)),
                // this is what we are testing
                $queryBuilder->expr()->like('aCsvField', $queryBuilder->createNamedParameter($searchWord))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        self::assertSame($expectedRows, $rows);
    }

    #[DataProvider('likeReturnsExpectedDataSetsDataProvider')]
    #[Test]
    /**
     * Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
     *       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
     *       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt')]
     */
    public function likeReturnsExpectedDataSetsForVarcharFields(string $section, string $searchWord, bool $caseSensitive, array $expectedRows, array $excludePlatforms): void
    {
        if ($caseSensitive === true) {
            // Skip case-sensitive test mutations, not implemented yet. Added as preparation to test different
            // approaches without having to clone tests in all test variants / changes.
            // @todo Remove when case-sensitive like() has been implemented.
            self::markTestSkipped('Case-sensitive like not implemented yet.');
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LikeAndNotLike_VARCHAR.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest_varchar');
        if ($excludePlatforms !== []) {
            $platform = $queryBuilder->getConnection()->getDatabasePlatform();
            foreach ($excludePlatforms as $excludePlatform) {
                if ($platform instanceof $excludePlatform) {
                    self::markTestSkipped('Excluded platform ' . $excludePlatform);
                }
            }
        }
        $rows = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest_varchar')
            ->where(
                // narrow down result set
                $queryBuilder->expr()->eq('aField', $queryBuilder->createNamedParameter($section)),
                // this is what we are testing
                $queryBuilder->expr()->like('aCsvField', $queryBuilder->createNamedParameter($searchWord))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        self::assertSame($expectedRows, $rows);
    }

    public static function notLikeReturnsExpectedDataSetsDataProvider(): \Generator
    {
        yield 'lowercase search word filters german umlauts in upper and lower casing #1' => [
            'section' => 'likecasing',
            'searchWord' => '%Überraschungen%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [
                // Exclude sqlite due to german umlauts.
                DoctrineSQLitePlatform::class,
            ],
        ];
        yield 'lowercase search word filters german umlauts in upper and lower casing #2' => [
            'section' => 'likecasing',
            'searchWord' => '%überraschungen%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 2,
                    'aCsvField' => 'Kleingeschriebenes überlebt halt ältere',
                ],
            ],
            'excludePlatforms' => [
                // Exclude sqlite due to german umlauts.
                DoctrineSQLitePlatform::class,
            ],
        ];
        yield 'lowercase ascii search word filters properly case-insensitive' => [
            'section' => 'likecasing',
            'searchWord' => '%klein%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 1,
                    'aCsvField' => 'Fächer, Überraschungen sind Äußerungen',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'escaped value with underscore matches properly' => [
            'section' => 'likeescape',
            // addcslashes() is used in escapeLikeWildcards()
            'searchWord' => '%' . addcslashes('underscore_escape_can_be_matched', '%_') . '%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 4,
                    'aCsvField' => 'not_underscore_value',
                ],
                1 => [
                    'uid' => 5,
                    'aCsvField' => 'Some value with a % in it',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'escaped value with wildcard search word matches properly' => [
            'section' => 'likeescape',
            // addcslashes() is used in escapeLikeWildcards()
            'searchWord' => '%' . addcslashes('a % in', '%_') . '%',
            'caseSensitive' => false,
            'expectedRows' => [
                0 => [
                    'uid' => 3,
                    'aCsvField' => 'underscore_escape_can_be_matched,second_value',
                ],
                1 => [
                    'uid' => 4,
                    'aCsvField' => 'not_underscore_value',
                ],
            ],
            'excludePlatforms' => [],
        ];
        // @todo Prepared test cases for case-sensitive like enforcement (not guaranteed yet), but required to ensure
        //       https://docs.typo3.org/m/typo3/reference-tca/11.5/en-us/ColumnsConfig/CommonProperties/Search.html#confval-case
        yield 'Case-sensitive for "Some"' => [
            'section' => 'casesensitive',
            'searchWord' => '%Some%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 7,
                    'aCsvField' => 'Another text with only lowercased some in it.',
                ],
                [
                    'uid' => 8,
                    'aCsvField' => 'Another text with only one uppercased SOME in it.',
                ],
                [
                    'uid' => 9,
                    'aCsvField' => 'Text with mixed case SoMe in it.',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'Case-sensitive for "some"' => [
            'section' => 'casesensitive',
            'searchWord' => '%some%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 8,
                    'aCsvField' => 'Another text with only one uppercased SOME in it.',
                ],
                [
                    'uid' => 9,
                    'aCsvField' => 'Text with mixed case SoMe in it.',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'Case-sensitive for "SOME"' => [
            'section' => 'casesensitive',
            'searchWord' => '%SOME%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 6,
                    'aCsvField' => 'Some text with to some in different casing.',
                ],
                [
                    'uid' => 7,
                    'aCsvField' => 'Another text with only lowercased some in it.',
                ],
                [
                    'uid' => 9,
                    'aCsvField' => 'Text with mixed case SoMe in it.',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'Case-sensitive for "SoMe"' => [
            'section' => 'casesensitive',
            'searchWord' => '%SoMe%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 6,
                    'aCsvField' => 'Some text with to some in different casing.',
                ],
                [
                    'uid' => 7,
                    'aCsvField' => 'Another text with only lowercased some in it.',
                ],
                [
                    'uid' => 8,
                    'aCsvField' => 'Another text with only one uppercased SOME in it.',
                ],
            ],
            'excludePlatforms' => [],
        ];
        yield 'Case-sensitive with german umlaut "ÜBERRASCHUNG"' => [
            'section' => 'casesensitive-umlaut',
            'searchWord' => '%ÜBERRASCHUNG%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 12,
                    'aCsvField' => 'Ein Lottogewinn ist schon eine starke Überraschung.',
                ],

            ],
            'excludePlatforms' => [],
        ];
        yield 'Case-sensitive with german umlaut "Überraschung"' => [
            'section' => 'casesensitive-umlaut',
            'searchWord' => '%Überraschung%',
            'caseSensitive' => true,
            'expectedRows' => [
                [
                    'uid' => 11,
                    'aCsvField' => 'Stay tuned, ÜBERRASCHUNG steht in den Starlöchern.',
                ],
            ],
            'excludePlatforms' => [],
        ];
    }

    #[DataProvider('notLikeReturnsExpectedDataSetsDataProvider')]
    #[Test]
    /**
     * Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
     *       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
     *       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt')]
     */
    public function notLikeReturnsExpectedDataSetsForTextFields(string $section, string $searchWord, bool $caseSensitive, array $expectedRows, array $excludePlatforms): void
    {
        if ($caseSensitive === true) {
            // Skip case-sensitive test mutations, not implemented yet. Added as preparation to test different
            // approaches without having to clone tests in all test variants / changes.
            // @todo Remove when case-sensitive notLike() has been implemented.
            self::markTestSkipped('Case-sensitive notLike() not implemented yet.');
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LikeAndNotLike_TEXT.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest');
        if ($excludePlatforms !== []) {
            $platform = $queryBuilder->getConnection()->getDatabasePlatform();
            foreach ($excludePlatforms as $excludePlatform) {
                if ($platform instanceof $excludePlatform) {
                    self::markTestSkipped('Excluded platform ' . $excludePlatform);
                }
            }
        }
        $rows = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest')
            ->where(
                // narrow down result set
                $queryBuilder->expr()->eq('aField', $queryBuilder->createNamedParameter($section)),
                // this is what we are testing
                $queryBuilder->expr()->notLike('aCsvField', $queryBuilder->createNamedParameter($searchWord))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        self::assertSame($expectedRows, $rows);
    }

    #[DataProvider('notLikeReturnsExpectedDataSetsDataProvider')]
    #[Test]
    /**
     * Note: SQLite does not properly work with non-ascii letters as search word for case-insensitive
     *       matching, UPPER() and LOWER() have the same issue, it only works with ascii letters.
     *       See: https://www.sqlite.org/src/doc/trunk/ext/icu/README.txt')]
     */
    public function notLikeReturnsExpectedDataSetsForVarcharFields(string $section, string $searchWord, bool $caseSensitive, array $expectedRows, array $excludePlatforms): void
    {
        if ($caseSensitive === true) {
            // Skip case-sensitive test mutations, not implemented yet. Added as preparation to test different
            // approaches without having to clone tests in all test variants / changes.
            // @todo Remove when case-sensitive notLike() has been implemented.
            self::markTestSkipped('Case-sensitive notLike() not implemented yet.');
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LikeAndNotLike_VARCHAR.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest_varchar');
        if ($excludePlatforms !== []) {
            $platform = $queryBuilder->getConnection()->getDatabasePlatform();
            foreach ($excludePlatforms as $excludePlatform) {
                if ($platform instanceof $excludePlatform) {
                    self::markTestSkipped('Excluded platform ' . $excludePlatform);
                }
            }
        }
        $rows = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest_varchar')
            ->where(
                // narrow down result set
                $queryBuilder->expr()->eq('aField', $queryBuilder->createNamedParameter($section)),
                // this is what we are testing
                $queryBuilder->expr()->notLike('aCsvField', $queryBuilder->createNamedParameter($searchWord))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        self::assertSame($expectedRows, $rows);
    }

    public static function likeOnIntegerFieldDataSets(): \Generator
    {
        yield 'Value in the middle is matched' => [
            'searchWord' => '%555%',
            'caseSensitive' => false,
            'expectedRows' => [
                [
                    'uid' => 1,
                    'aCsvField' => 25556,
                ],
                [
                    'uid' => 2,
                    'aCsvField' => 25557,
                ],
            ],
        ];
        yield 'Value in the beginning is matched' => [
            'searchWord' => '%25%',
            'caseSensitive' => false,
            'expectedRows' => [
                [
                    'uid' => 1,
                    'aCsvField' => 25556,
                ],
                [
                    'uid' => 2,
                    'aCsvField' => 25557,
                ],
            ],
        ];
        yield 'Value in the end is matched' => [
            'searchWord' => '%57%',
            'caseSensitive' => false,
            'expectedRows' => [
                [
                    'uid' => 2,
                    'aCsvField' => 25557,
                ],
                [
                    'uid' => 3,
                    'aCsvField' => 59957,
                ],
            ],
        ];
    }

    #[Group('not-postgres')]
    #[DataProvider('likeOnIntegerFieldDataSets')]
    #[Test]
    /**
     * @todo PostgreSQL is picky when using LIKE or ILIKE on a field or value not being a compatible text-type,
     *       requiring explicitly type casting. MySQL, MariaDB and SQLite are more forgiving and supports LIKE
     *       comparisons on these fields or values. Excluded for PostgresSQL until a solution is implemented.
     */
    public function likeOnIntegerFieldReturnsExpectedDataSet(string $searchWord, bool $caseSensitive, array $expectedRows): void
    {
        if ($caseSensitive === true) {
            // Skip case-sensitive test mutations, not implemented yet. Added as preparation to test different
            // approaches without having to clone tests in all test variants / changes.
            // @todo Remove when case-sensitive like() has been implemented.
            self::markTestSkipped('Case-sensitive like not implemented yet.');
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LikeAndNotLike_INTEGER.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest_integer');
        $rows = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest_integer')
            ->where(
                // narrow down result set
                $queryBuilder->expr()->eq('aField', $queryBuilder->createNamedParameter('integer')),
                // this is what we are testing
                $queryBuilder->expr()->like('aCsvField', $queryBuilder->createNamedParameter($searchWord))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        self::assertSame($expectedRows, $rows);
    }

    public static function notLikeOnIntegerFieldDataSets(): \Generator
    {
        yield 'Value in the middle is excluded' => [
            'searchWord' => '%555%',
            'caseSensitive' => false,
            'expectedRows' => [
                [
                    'uid' => 3,
                    'aCsvField' => 59957,
                ],
            ],
        ];
        yield 'Value in the beginning is excluded' => [
            'searchWord' => '%25%',
            'caseSensitive' => false,
            'expectedRows' => [
                [
                    'uid' => 3,
                    'aCsvField' => 59957,
                ],
            ],
        ];
        yield 'Value in the end is excluded' => [
            'searchWord' => '%57%',
            'caseSensitive' => false,
            'expectedRows' => [
                [
                    'uid' => 1,
                    'aCsvField' => 25556,
                ],
            ],
        ];
    }

    #[Group('not-postgres')]
    #[DataProvider('notLikeOnIntegerFieldDataSets')]
    #[Test]
    /**
     * @todo PostgreSQL is picky when using LIKE or ILIKE on a field or value not being a compatible text-type,
     *       requiring explicitly type casting. MySQL, MariaDB and SQLite are more forgiving and supports LIKE
     *       comparisons on these fields or values. Excluded for PostgresSQL until a solution is implemented.
     */
    public function notLikeOnIntegerFieldReturnsExpectedDataSet(string $searchWord, bool $caseSensitive, array $expectedRows): void
    {
        if ($caseSensitive === true) {
            // Skip case-sensitive test mutations, not implemented yet. Added as preparation to test different
            // approaches without having to clone tests in all test variants / changes.
            // @todo Remove when case-sensitive notLike() has been implemented.
            self::markTestSkipped('Case-sensitive notLike() not implemented yet.');
        }
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LikeAndNotLike_INTEGER.csv');
        $queryBuilder = (new ConnectionPool())->getQueryBuilderForTable('tx_expressionbuildertest_integer');
        $rows = $queryBuilder
            ->select('uid', 'aCsvField')
            ->from('tx_expressionbuildertest_integer')
            ->where(
                // narrow down result set
                $queryBuilder->expr()->eq('aField', $queryBuilder->createNamedParameter('integer')),
                // this is what we are testing
                $queryBuilder->expr()->notLike('aCsvField', $queryBuilder->createNamedParameter($searchWord))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        self::assertSame($expectedRows, $rows);
    }
}
