<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Functional\Database;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class QueryGeneratorTest extends FunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = new LanguageService();
    }

    /**
     * @return array
     */
    public function getSubscriptReturnsExpectedValuesDataProvider(): array
    {
        return [
            'multidimensional array input' => [
                [
                    'foo' => [
                        'bar' => 1,
                        'baz' => [
                            'jane' => 1,
                            'john' => 'doe',
                        ],
                        'fae' => 1,
                    ],
                    'don' => [
                        'dan' => 1,
                        'jim' => [
                            'jon' => 1,
                            'jin' => 'joh',
                        ],
                    ],
                    'one' => [
                        'two' => 1,
                        'three' => [
                            'four' => 1,
                            'five' =>'six',
                        ],
                    ]
                ],
                [
                    0 => 'foo',
                    1 => 'bar',
                ],
            ],
            'array with multiple entries input' => [
                [
                    'foo' => 1,
                    'bar' => 2,
                    'baz' => 3,
                    'don' => 4,
                ],
                [
                    0 => 'foo',
                ],
            ],
            'array with one entry input' => [
                [
                    'foo' => 'bar',
                ],
                [
                    0 => 'foo',
                ],
            ],
            'empty array input' => [
                [],
                [
                    0 => null,
                ],
            ],
            'empty multidimensional array input' => [
                [[[[]]], [[]], [[]]],
                [
                    0 => 0,
                    1 => 0,
                    2 => 0,
                    3 => null,
                ],
            ],
            'null input' => [
                null,
                [],
            ],
            'string input' => [
                'foo bar',
                [],
            ],
            'numeric input' => [
                3.14,
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getSubscriptReturnsExpectedValuesDataProvider
     * @param $input
     * @param array $expectedArray
     */
    public function getSubscriptReturnsExpectedValues($input, array $expectedArray)
    {
        $subject = new QueryGenerator();
        $this->assertSame($expectedArray, $subject->getSubscript($input));
    }

    public function arbitraryDataIsEscapedDataProvider(): array
    {
        $dataSet = [];
        $injectors = [
            // INJ'ECT
            'INJ%quoteCharacter%ECT',
            // INJ '--
            // ' ECT
            'INJ %quoteCharacter%%commentStart% %commentEnd%%quoteCharacter% ECT'
        ];
        $comparisons = array_keys((new QueryGenerator())->compSQL);
        foreach ($injectors as $injector) {
            foreach ($comparisons as $comparison) {
                $dataSet[] = [
                    $injector,
                    [
                        'queryTable' => 'tt_content',
                        'queryFields' => 'uid,' . $injector,
                        'queryGroup' => $injector,
                        'queryOrder' => $injector,
                        'queryLimit' => $injector,
                        'queryConfig' => serialize([
                            [
                                'operator' => $injector,
                                'type' => 'FIELD_category_field', // falls back to CType (first field)
                                'comparison' => $comparison,
                                'inputValue' => $injector,

                            ],
                            [
                                'operator' => $injector,
                                'type' => 'FIELD_category_field',
                                'comparison' => $comparison,
                                'inputValue' => $injector,

                            ],
                        ]),
                    ],
                ];
            }
        }
        return $dataSet;
    }

    /**
     * @param string $injector
     * @param array $settings
     *
     * @test
     * @dataProvider arbitraryDataIsEscapedDataProvider
     * @throws \Doctrine\DBAL\DBALException
     */
    public function arbitraryDataIsEscaped(string $injector, array $settings)
    {
        $databasePlatform = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content')->getDatabasePlatform();
        $replacements = [
            '%quoteCharacter%' => $databasePlatform->getStringLiteralQuoteCharacter(),
            '%commentStart%' => $databasePlatform->getSqlCommentStartString(),
            '%commentEnd%' => $databasePlatform->getSqlCommentEndString()
        ];
        $injector = str_replace(array_keys($replacements), $replacements, $injector);
        $settings = $this->prepareSettings($settings, $replacements);

        $queryGenerator = new QueryGenerator();
        $queryGenerator->init('queryConfig', $settings['queryTable']);
        $queryGenerator->makeSelectorTable($settings);
        $queryGenerator->enablePrefix = true;

        $queryString = $queryGenerator->getQuery($queryGenerator->queryConfig);
        $query = $queryGenerator->getSelectQuery($queryString);

        self::assertNotContains($injector, $query);
    }

    protected function prepareSettings(array $settings, array $replacements): array
    {
        foreach ($settings as $settingKey => &$settingValue) {
            if (is_string($settingValue)) {
                $settingValue = str_replace(array_keys($replacements), $replacements, $settingValue);
            }
            if (is_array($settingValue)) {
                $settingValue = $this->prepareSettings($settingValue, $replacements);
            }
        }
        return $settings;
    }
}
