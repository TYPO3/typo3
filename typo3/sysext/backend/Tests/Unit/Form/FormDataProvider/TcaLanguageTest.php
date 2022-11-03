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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaLanguage;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaLanguageTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Default LANG mock just returns incoming value as label if calling ->sL()
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->with(self::anything())->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceMock;
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addDataIgnoresEmptyOrWrongTcaType(): void
    {
        $input = $this->getDefaultResultArray(['config' => ['type' => 'none']]);
        self::assertEquals($input, (new TcaLanguage())->addData($input));
    }

    /**
     * @test
     */
    public function addDataRespectsCustomRenderType(): void
    {
        $input = $this->getDefaultResultArray(['config' => ['renderType' => 'customRenderType']]);

        self::assertEquals(
            'customRenderType',
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['renderType']
        );
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfAnItemIsNotAnArray(): void
    {
        $input = $this->getDefaultResultArray(['config' => ['items' => ['foo']]]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1439288036);

        (new TcaLanguage())->addData($input);
    }

    /**
     * @test
     */
    public function addDataAddsAllSiteLanguages(): void
    {
        $input = $this->getDefaultResultArray([], $this->getDefaultSystemLanguages());

        $expected = [
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.siteLanguages', '--div--', null, null, null],
            ['English', 0, 'flags-us', null, null],
            ['Danish', 13, 'flags-dk', null, null],
            ['German', 14, 'flags-de', null, null],
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.specialLanguages', '--div--', null, null, null],
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1, 'flags-multiple', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     */
    public function addDataOmitsLanguageAllForPages(): void
    {
        $input = $this->getDefaultResultArray([], $this->getDefaultSystemLanguages(), [], ['tableName' => 'pages']);

        $expected = [
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.siteLanguages', '--div--', null, null, null],
            ['English', 0, 'flags-us', null, null],
            ['Danish', 13, 'flags-dk', null, null],
            ['German', 14, 'flags-de', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     */
    public function addDataOmitsLanguageAllIfNotAllowed(): void
    {
        $systemLanguages =  $this->getDefaultSystemLanguages();
        // ALL is not allowed
        unset($systemLanguages[-1]);

        $input = $this->getDefaultResultArray([], $systemLanguages);

        $expected = [
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.siteLanguages', '--div--', null, null, null],
            ['English', 0, 'flags-us', null, null],
            ['Danish', 13, 'flags-dk', null, null],
            ['German', 14, 'flags-de', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     */
    public function addDataAddsUserDefinedItems(): void
    {
        $input = $this->getDefaultResultArray(
            [
                'config' => [
                    'items' => [
                        8 => [
                            'User defined', 8, 'some-icon',
                        ],
                    ],
                ],
            ],
            $this->getDefaultSystemLanguages(),
            [],
            ['tableName' => 'pages']
        );

        $expected = [
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.siteLanguages', '--div--', null, null, null],
            ['English', 0, 'flags-us', null, null],
            ['Danish', 13, 'flags-dk', null, null],
            ['German', 14, 'flags-de', null, null],
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.specialLanguages', '--div--', null, null, null],
            ['User defined', 8, 'some-icon', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     */
    public function addDataAddsUserDefinedItemsOnEmptySystemLanguages(): void
    {
        $input = $this->getDefaultResultArray(
            [
                'config' => [
                    'items' => [
                        8 => [
                            'User defined', 8, 'some-icon',
                        ],
                    ],
                    'disableNoMatchingValueElement' => true,
                ],
            ]
        );

        $expected = [
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.specialLanguages', '--div--', null, null, null],
            ['User defined', 8, 'some-icon', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     */
    public function addDataRemovesAllItemsByEmptyKeepItems(): void
    {
        $input = $this->getDefaultResultArray(
            [],
            $this->getDefaultSystemLanguages(),
            [],
            [
                'pageTsConfig' => [
                    'TCEFORM.' => [
                        'aTable.' => [
                            'aField.' => [
                                'keepItems' => '',
                            ],
                        ],
                    ],
                ],
            ]
        );

        self::assertEmpty((new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']);
    }

    /**
     * @test
     */
    public function addDataRespectsKeepItems(): void
    {
        $input = $this->getDefaultResultArray(
            [],
            $this->getDefaultSystemLanguages(),
            [],
            [
                'pageTsConfig' => [
                    'TCEFORM.' => [
                        'aTable.' => [
                            'aField.' => [
                                'keepItems' => '0,13',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $expected = [
            ['English', 0, 'flags-us', null, null],
            ['Danish', 13, 'flags-dk', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     */
    public function addDataRespectsAddItems(): void
    {
        $input = $this->getDefaultResultArray(
            [],
            [],
            ['aField' => 5],
            [
                'pageTsConfig' => [
                    'TCEFORM.' => [
                        'aTable.' => [
                            'aField.' => [
                                'addItems.' => [
                                    '8' => 'User defined',
                                    '8.' => [
                                        'icon' => 'some-icon',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $expected = [
            ['[ LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue ]', 5, null, null, null],
            ['User defined', 8, 'some-icon', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     */
    public function addDataRespectsRemoveItems(): void
    {
        $input = $this->getDefaultResultArray(
            [],
            $this->getDefaultSystemLanguages(),
            [],
            [
                'pageTsConfig' => [
                    'TCEFORM.' => [
                        'aTable.' => [
                            'aField.' => [
                                'removeItems' => '-1,13,14',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $expected = [
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.siteLanguages', '--div--', null, null, null],
            ['English', 0, 'flags-us', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     */
    public function addDataAddsInvalidDatabaseValue(): void
    {
        $input = $this->getDefaultResultArray([], $this->getDefaultSystemLanguages(), ['aField' => 5], ['tableName' => 'pages']);

        $expected = [
            ['[ LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue ]', 5, null, null, null],
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.siteLanguages', '--div--', null, null, null],
            ['English', 0, 'flags-us', null, null],
            ['Danish', 13, 'flags-dk', null, null],
            ['German', 14, 'flags-de', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }
    /**
     * @test
     */
    public function addDataRepsetcsConfigurationOnAddingInvalidDatabaseValue(): void
    {
        $input = $this->getDefaultResultArray(
            [],
            [],
            ['aField' => 5],
            [
                'pageTsConfig' => [
                    'TCEFORM.' => [
                        'aTable.' => [
                            'aField.' => [
                                'disableNoMatchingValueElement' => '1',
                             ],
                        ],
                    ],
                ],
            ]
        );

        // Adding invalid value is disabled in TSconfig
        self::assertEmpty((new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']);

        $input = $this->getDefaultResultArray(['config' => ['disableNoMatchingValueElement' => true]], [], ['aField' => 5]);

        // Adding invalid value is disabled in columns config
        self::assertEmpty((new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']);

        $input = $this->getDefaultResultArray(
            [
                'config' => [
                    'items' => [
                        8 => [
                            'User defined', 8, 'some-icon',
                        ],
                    ],
                ],
            ],
            [],
            ['aField' => 5],
            [
                'pageTsConfig' => [
                    'TCEFORM.' => [
                        'aTable.' => [
                            'aField.' => [
                                'noMatchingValue_label' => 'Custom label',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $expected = [
            ['Custom label', 5, null, null, null],
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.specialLanguages', '--div--', null, null, null],
            ['User defined', 8, 'some-icon', null, null],
        ];

        // Custom label is respected
        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     */
    public function addDataRespectsAltLabels(): void
    {
        $input = $this->getDefaultResultArray(
            [],
            $this->getDefaultSystemLanguages(),
            [],
            [
                'pageTsConfig' => [
                    'TCEFORM.' => [
                        'aTable.' => [
                            'aField.' => [
                                'altLabels.' => [
                                    '0' => 'Default Language',
                                    '14' => 'Deutsch',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $expected = [
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.siteLanguages', '--div--', null, null, null],
            ['Default Language', 0, 'flags-us', null, null],
            ['Danish', 13, 'flags-dk', null, null],
            ['Deutsch', 14, 'flags-de', null, null],
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.specialLanguages', '--div--', null, null, null],
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1, 'flags-multiple', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     */
    public function addDataRespectsAltIcons(): void
    {
        $input = $this->getDefaultResultArray(
            [],
            $this->getDefaultSystemLanguages(),
            [],
            [
                'pageTsConfig' => [
                    'TCEFORM.' => [
                        'aTable.' => [
                            'aField.' => [
                                'altIcons.' => [
                                    '0' => 'alternative-icon-default',
                                    '14' => 'alternative-icon-german',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $expected = [
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.siteLanguages', '--div--', null, null, null],
            ['English', 0, 'alternative-icon-default', null, null],
            ['Danish', 13, 'flags-dk', null, null],
            ['German', 14, 'alternative-icon-german', null, null],
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.specialLanguages', '--div--', null, null, null],
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1, 'flags-multiple', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     */
    public function addDataRemovesLastItemIfDivider(): void
    {
        $input = $this->getDefaultResultArray(
            [],
            $this->getDefaultSystemLanguages(),
            [],
            [
                'pageTsConfig' => [
                    'TCEFORM.' => [
                        'aTable.' => [
                            'aField.' => [
                                'removeItems' => '-1',
                            ],
                        ],
                    ],
                ],
            ],
        );

        $expected = [
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.siteLanguages', '--div--', null, null, null],
            ['English', 0, 'flags-us', null, null],
            ['Danish', 13, 'flags-dk', null, null],
            ['German', 14, 'flags-de', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    /**
     * @test
     * @dataProvider addDataAddsAllSiteLanguagesDataProvider
     */
    public function addDataAddsAllSiteLanguagesFromAllSites(array $config): void
    {
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->method('getAllSites')->willReturn([
            new Site('site-1', 1, [
               'base' => '/',
               'languages' => [
                   [
                       'title' => 'English',
                       'languageId' => 0,
                       'base' => '/',
                       'locale' => 'en_US',
                       'flag' => 'us',
                   ],
                   [
                       'title' => 'German',
                       'languageId' => 2,
                       'base' => '/de/',
                       'locale' => 'de_DE',
                       'flag' => 'de',
                   ],
               ],
            ]),
            new Site('site-2', 2, [
               'base' => '/',
               'languages' => [
                   [
                       'title' => 'German',
                       'languageId' => 0,
                       'base' => '/',
                       'locale' => 'de_DE',
                       'flag' => 'de',
                   ],
               ],
            ]),
        ]);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinder);

        $input = $this->getDefaultResultArray([], [], [], $config);

        $expected = [
            ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.siteLanguages', '--div--', null, null, null],
            ['English [Site: site-1], German [Site: site-2]', 0, 'flags-us', null, null],
            ['German [Site: site-1]', 2, 'flags-de', null, null],
        ];

        self::assertEquals(
            $expected,
            (new TcaLanguage())->addData($input)['processedTca']['columns']['aField']['config']['items']
        );
    }

    public function addDataAddsAllSiteLanguagesDataProvider(): \Generator
    {
        yield 'On root level pid=0' => [
            [
                'effectivePid' => 0,
            ],
        ];
        yield 'Without site configuration' => [
            [
                'site' => new NullSite(),
            ],
        ];
    }

    protected function getDefaultResultArray(
        array $fieldConfig = [],
        array $systemLanguages = [],
        array $databaseRow = [],
        array $additionalConfiguration = []
    ): array {
        return array_replace_recursive([
            'tableName' => 'aTable',
            'systemLanguageRows' => array_replace_recursive([], $systemLanguages),
            'effectivePid' => 1,
            'site' => new Site('some-site', 1, []),
            'databaseRow' => array_replace_recursive([], $databaseRow),
            'processedTca' => [
                'columns' => [
                     'aField' => array_replace_recursive([
                         'config' => [
                             'type' => 'language',
                         ],
                     ], $fieldConfig),
                ],
            ],
        ], $additionalConfiguration);
    }

    protected function getDefaultSystemLanguages(array $additionalLanguages = []): array
    {
        return array_replace_recursive([
            -1 => [
                'uid' => -1,
                'title' => 'All Languages',
                'iso' => 'DEF',
                'flagIconIdentifier' => 'flags-multiple',
            ],
            0 => [
                'uid' => 0,
                'title' => 'English',
                'iso' => 'DEF',
                'flagIconIdentifier' => 'flags-us',
            ],
            13 => [
                'uid' => 13,
                'title' => 'Danish',
                'iso' => 'da',
                'flagIconIdentifier' => 'flags-dk',
            ],
            14 => [
                'uid' => 14,
                'title' => 'German',
                'iso' => 'de',
                'flagIconIdentifier' => 'flags-de',
            ],
        ], $additionalLanguages);
    }
}
