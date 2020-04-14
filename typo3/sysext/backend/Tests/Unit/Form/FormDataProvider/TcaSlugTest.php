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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSlug;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaSlugTest extends UnitTestCase
{
    /**
     * @var TcaSlug
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TcaSlug();
    }

    /**
     * Data provider for getSlugPrefixForDefinedLanguagesAndUserFunc
     *
     * @return array [$input, $expected]
     */
    public function resultArrayDataProvider(): array
    {
        return [
            'Language default [0]' => [
                [
                    'tableName' => 'aTable',
                    'site' => new Site('www.foo.de', 0, [
                        'languages' => [
                            [
                                'languageId' => 0,
                                'locale' => 'en_US.UTF-8',
                                'base' => '/en/'
                            ],
                            [
                                'languageId' => 1,
                                'locale' => 'de_DE.UTF-8',
                                'base' => '/de/'
                            ]
                        ]
                    ]),
                    'databaseRow' => [
                        'sys_language_uid' => [0]
                    ],
                    'processedTca' => [
                        'ctrl' => [
                            'languageField' => 'sys_language_uid'
                        ],
                        'columns' => [
                            'slugField' => [
                                'config' => [
                                    'type' => 'slug'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'tableName' => 'aTable',
                    'site' => new Site('www.foo.de', 0, [
                        'languages' => [
                            [
                                'languageId' => 0,
                                'locale' => 'en_US.UTF-8',
                                'base' => '/en/'
                            ],
                            [
                                'languageId' => 1,
                                'locale' => 'de_DE.UTF-8',
                                'base' => '/de/'
                            ]
                        ]
                    ]),
                    'databaseRow' => [
                        'sys_language_uid' => [0]
                    ],
                    'processedTca' => [
                        'ctrl' => [
                            'languageField' => 'sys_language_uid'
                        ],
                        'columns' => [
                            'slugField' => [
                                'config' => [
                                    'type' => 'slug',
                                    'appearance' => [
                                        'prefix' => '/en'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'customData' => [
                        'slugField' => [
                            'slugPrefix' => '/en'
                        ]
                    ]
                ]
            ],
            'Language 1' => [
                [
                    'tableName' => 'aTable',
                    'site' => new Site('www.foo.de', 0, [
                        'languages' => [
                            [
                                'languageId' => 0,
                                'locale' => 'en_US.UTF-8',
                                'base' => '/en/'
                            ],
                            [
                                'languageId' => 1,
                                'locale' => 'de_DE.UTF-8',
                                'base' => '/de/'
                            ]
                        ]
                    ]),
                    'databaseRow' => [
                        'sys_language_uid' => [1]
                    ],
                    'processedTca' => [
                        'ctrl' => [
                            'languageField' => 'sys_language_uid'
                        ],
                        'columns' => [
                            'slugField' => [
                                'config' => [
                                    'type' => 'slug'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'tableName' => 'aTable',
                    'site' => new Site('www.foo.de', 0, [
                        'languages' => [
                            [
                                'languageId' => 0,
                                'locale' => 'en_US.UTF-8',
                                'base' => '/en/'
                            ],
                            [
                                'languageId' => 1,
                                'locale' => 'de_DE.UTF-8',
                                'base' => '/de/'
                            ]
                        ]
                    ]),
                    'databaseRow' => [
                        'sys_language_uid' => [1]
                    ],
                    'processedTca' => [
                        'ctrl' => [
                            'languageField' => 'sys_language_uid'
                        ],
                        'columns' => [
                            'slugField' => [
                                'config' => [
                                    'type' => 'slug',
                                    'appearance' => [
                                        'prefix' => '/de'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'customData' => [
                        'slugField' => [
                            'slugPrefix' => '/de'
                        ]
                    ]
                ]
            ],
            'Language -1' => [
                [
                    'tableName' => 'aTable',
                    'site' => new Site('www.foo.de', 0, [
                        'languages' => [
                            [
                                'languageId' => 0,
                                'locale' => 'en_US.UTF-8',
                                'base' => '/en/'
                            ],
                            [
                                'languageId' => 1,
                                'locale' => 'de_DE.UTF-8',
                                'base' => '/de/'
                            ]
                        ]
                    ]),
                    'databaseRow' => [
                        'sys_language_uid' => [-1]
                    ],
                    'processedTca' => [
                        'ctrl' => [
                            'languageField' => 'sys_language_uid'
                        ],
                        'columns' => [
                            'slugField' => [
                                'config' => [
                                    'type' => 'slug'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'tableName' => 'aTable',
                    'site' => new Site('www.foo.de', 0, [
                        'languages' => [
                            [
                                'languageId' => 0,
                                'locale' => 'en_US.UTF-8',
                                'base' => '/en/'
                            ],
                            [
                                'languageId' => 1,
                                'locale' => 'de_DE.UTF-8',
                                'base' => '/de/'
                            ]
                        ]
                    ]),
                    'databaseRow' => [
                        'sys_language_uid' => [-1]
                    ],
                    'processedTca' => [
                        'ctrl' => [
                            'languageField' => 'sys_language_uid'
                        ],
                        'columns' => [
                            'slugField' => [
                                'config' => [
                                    'type' => 'slug',
                                    'appearance' => [
                                        'prefix' => '/en'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'customData' => [
                        'slugField' => [
                            'slugPrefix' => '/en'
                        ]
                    ]
                ]
            ],
            'UserFunc' => [
                [
                    'tableName' => 'aTable',
                    'site' => new Site('www.foo.de', 0, [
                        'languages' => [
                            [
                                'languageId' => 0,
                                'locale' => 'en_US.UTF-8',
                                'base' => '/en/'
                            ],
                            [
                                'languageId' => 1,
                                'locale' => 'de_DE.UTF-8',
                                'base' => '/de/'
                            ]
                        ]
                    ]),
                    'databaseRow' => [
                        'sys_language_uid' => [0]
                    ],
                    'processedTca' => [
                        'ctrl' => [
                            'languageField' => 'sys_language_uid'
                        ],
                        'columns' => [
                            'slugField' => [
                                'config' => [
                                    'type' => 'slug',
                                    'appearance' => [
                                        'prefix' => function (array $parameters, TcaSlug $reference): string {
                                            return $parameters['site']->getIdentifier()
                                                . '-'
                                                . $parameters['languageId']
                                                . '-'
                                                . $parameters['table']
                                                . '-'
                                                . $parameters['row']['sys_language_uid'][0];
                                        }
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'tableName' => 'aTable',
                    'site' => new Site('www.foo.de', 0, [
                        'languages' => [
                            [
                                'languageId' => 0,
                                'locale' => 'en_US.UTF-8',
                                'base' => '/en/'
                            ],
                            [
                                'languageId' => 1,
                                'locale' => 'de_DE.UTF-8',
                                'base' => '/de/'
                            ]
                        ]
                    ]),
                    'databaseRow' => [
                        'sys_language_uid' => [0]
                    ],
                    'processedTca' => [
                        'ctrl' => [
                            'languageField' => 'sys_language_uid'
                        ],
                        'columns' => [
                            'slugField' => [
                                'config' => [
                                    'type' => 'slug',
                                    'appearance' => [
                                        'prefix' => 'www.foo.de-0-aTable-0'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'customData' => [
                        'slugField' => [
                            'slugPrefix' => 'www.foo.de-0-aTable-0'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider resultArrayDataProvider
     *
     * @param array $input
     * @param array $expected
     */
    public function getSlugPrefixForDefinedLanguagesAndUserFunc(array $input, array $expected): void
    {
        self::assertEquals($expected, $this->subject->addData($input));
    }
}
