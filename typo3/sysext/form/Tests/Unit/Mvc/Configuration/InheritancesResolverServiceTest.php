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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\CycleInheritancesException;
use TYPO3\CMS\Form\Mvc\Configuration\InheritancesResolverService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class InheritancesResolverServiceTest extends UnitTestCase
{
    /**
     * @var InheritancesResolverService
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new InheritancesResolverService();
    }

    #[Test]
    public function getDocExampleInheritance(): void
    {
        $input = [
            'Form' => [
                'part1' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => 'value3',
                ],
                'part2' => [
                    '__inheritances' => [
                        10 => 'Form.part1',
                    ],
                    'key2' => 'another_value',
                ],
            ],
        ];

        $expected = [
            'Form' => [
                'part1' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => 'value3',
                ],
                'part2' => [
                    'key1' => 'value1',
                    'key2' => 'another_value',
                    'key3' => 'value3',
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration());
    }

    #[Test]
    public function getMergedConfigurationSimpleInheritance(): void
    {
        $input = [
            'Form' => [
                'klaus01' => [
                    'key01' => 'value',
                    'key02' => [
                        'key03' => 'value',
                    ],
                ],
                'klaus02' => [
                    '__inheritances' => [
                        10 => 'Form.klaus01',
                    ],
                ],
            ],
        ];

        $expected = [
            'Form' => [
                'klaus01' => [
                    'key01' => 'value',
                    'key02' => [
                        'key03' => 'value',
                    ],
                ],
                'klaus02' => [
                    'key01' => 'value',
                    'key02' => [
                        'key03' => 'value',
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration());
    }

    #[Test]
    public function getMergedConfigurationSimpleInheritanceOverrideValue(): void
    {
        $input = [
            'Form' => [
                'klaus01' => [
                    'key' => 'value',
                ],
                'klaus02' => [
                    '__inheritances' => [
                        10 => 'Form.klaus01',
                    ],
                    'key' => 'value override',
                ],
            ],
        ];

        $expected = [
            'Form' => [
                'klaus01' => [
                    'key' => 'value',
                ],
                'klaus02' => [
                    'key' => 'value override',
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration());
    }

    #[Test]
    public function getMergedConfigurationSimpleInheritanceRemoveValue(): void
    {
        $input = [
            'Form' => [
                'klaus01' => [
                    'key01' => [
                        'key02' => 'value',
                    ],
                    'key02' => [
                        10 => [
                            'key' => 'value',
                        ],
                        20 => [
                            'key' => 'value',
                        ],
                    ],
                ],
                'klaus02' => [
                    '__inheritances' => [
                        10 => 'Form.klaus01',
                    ],
                    'key01' => null,
                    'key02' => [
                        10 => null,
                        20 => [
                            'key' => null,
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'Form' => [
                'klaus01' => [
                    'key01' => [
                        'key02' => 'value',
                    ],
                    'key02' => [
                        10 => [
                            'key' => 'value',
                        ],
                        20 => [
                            'key' => 'value',
                        ],
                    ],
                ],
                'klaus02' => [
                    'key01' => null,
                    'key02' => [
                        10 => null,
                        20 => [
                            'key' => null,
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration());
    }

    #[Test]
    public function getMergedConfigurationSimpleMixin(): void
    {
        $input = [
            'Form' => [
                'mixin01' => [
                    'key' => 'value',
                ],
                'klaus01' => [
                    '__inheritances' => [
                        10 => 'Form.mixin01',
                    ],
                ],
                'klaus02' => [
                    'key' => [
                        '__inheritances' => [
                            10 => 'Form.mixin01',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'Form' => [
                'mixin01' => [
                    'key' => 'value',
                ],
                'klaus01' => [
                    'key' => 'value',
                ],
                'klaus02' => [
                    'key' => [
                        'key' => 'value',
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration());
    }

    #[Test]
    public function getMergedConfigurationAdvancedMixin(): void
    {
        $input = [
            'Form' => [
                'mixin01' => [
                    'key01' => 'value01',
                    'key02' => 'value02',
                ],
                'mixin02' => [
                    '__inheritances' => [
                        10 => 'Form.mixin01',
                    ],
                ],
                'mixin03' => [
                    'key03' => 'value03',
                ],

                'klaus01' => [
                    '__inheritances' => [
                        10 => 'Form.mixin01',
                    ],
                    'key01' => 'value01 override 01',
                ],
                'klaus02' => [
                    '__inheritances' => [
                        10 => 'Form.klaus01',
                        20 => 'Form.mixin03',
                    ],
                    'key01' => 'value01 override 02',
                    'key02' => [
                        'horst01' => 'gerda01',
                    ],
                    'key03' => [
                        '__inheritances' => [
                            10 => 'Form.mixin02',
                        ],
                        'key02' => null,
                    ],
                ],
                'klaus03' => [
                    '__inheritances' => [
                        10 => 'Form.klaus02',
                    ],
                ],
            ],
        ];

        $expected = [
            'Form' => [
                'mixin01' => [
                    'key01' => 'value01',
                    'key02' => 'value02',
                ],
                'mixin02' => [
                    'key01' => 'value01',
                    'key02' => 'value02',
                ],
                'mixin03' => [
                    'key03' => 'value03',
                ],
                'klaus01' => [
                    'key01' => 'value01 override 01',
                    'key02' => 'value02',
                ],
                'klaus02' => [
                    'key01' => 'value01 override 02',
                    'key02' => [
                        'horst01' => 'gerda01',
                    ],
                    'key03' => [
                        'key01' => 'value01',
                        'key02' => null,
                    ],
                ],
                'klaus03' => [
                    'key01' => 'value01 override 02',
                    'key02' => [
                        'horst01' => 'gerda01',
                    ],
                    'key03' => [
                        'key01' => 'value01',
                        'key02' => null,
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration());
    }

    #[Test]
    public function getMergedConfigurationResolvesInheritanceWithNumericKeys(): void
    {
        $input = [
            'finishers' => [
                1 => [
                    'identifier' => 'SaveEventFinisher',
                    'options' => [
                        1 => [
                            'table' => 'some_table',
                            'mode' => 'insert',
                            'elements' => [
                                'youtube-link' => [
                                    'mapOnDatabaseColumn' => 'link',
                                ],
                            ],
                            'databaseColumnMappings' => [
                                'pid' => [
                                    'value' => 8,
                                ],
                                'crdate' => [
                                    'value' => '{__currentTimestamp}',
                                ],
                            ],
                        ],
                        2 => [
                            '__inheritances' => [
                                10 => 'finishers.1.options.1',
                            ],
                            'elements' => [
                                'download-link' => [
                                    'mapOnDatabaseColumn' => 'link',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'finishers' => [
                1 => [
                    'identifier' => 'SaveEventFinisher',
                    'options' => [
                        1 => [
                            'table' => 'some_table',
                            'mode' => 'insert',
                            'elements' => [
                                'youtube-link' => [
                                    'mapOnDatabaseColumn' => 'link',
                                ],
                            ],
                            'databaseColumnMappings' => [
                                'pid' => [
                                    'value' => 8,
                                ],
                                'crdate' => [
                                    'value' => '{__currentTimestamp}',
                                ],
                            ],
                        ],
                        2 => [
                            'table' => 'some_table',
                            'mode' => 'insert',
                            'elements' => [
                                'youtube-link' => [
                                    'mapOnDatabaseColumn' => 'link',
                                ],
                                'download-link' => [
                                    'mapOnDatabaseColumn' => 'link',
                                ],
                            ],
                            'databaseColumnMappings' => [
                                'pid' => [
                                    'value' => 8,
                                ],
                                'crdate' => [
                                    'value' => '{__currentTimestamp}',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration());
    }

    #[Test]
    public function getMergedConfigurationResolvesInheritancesWithAndWithoutVendorNamespacePrefix(): void
    {
        $input = [
            'mixin01' => [
                'key01' => 'value01',
                'key02' => 'value02',
            ],
            'mixin02' => [
                '__inheritances' => [
                    10 => 'mixin01',
                ],
            ],
            'mixin03' => [
                'key03' => 'value03',
            ],

            'klaus01' => [
                '__inheritances' => [
                    10 => 'TYPO3.CMS.Form.mixin01',
                ],
                'key01' => 'value01 override 01',
            ],
            'klaus02' => [
                '__inheritances' => [
                    10 => 'klaus01',
                    20 => 'TYPO3.CMS.Form.mixin03',
                ],
                'key01' => 'value01 override 02',
                'key02' => [
                    'horst01' => 'gerda01',
                ],
                'key03' => [
                    '__inheritances' => [
                        10 => 'mixin02',
                    ],
                    'key02' => null,
                ],
            ],
            'klaus03' => [
                '__inheritances' => [
                    10 => 'klaus02',
                ],
            ],
        ];

        $expected = [
            'mixin01' => [
                'key01' => 'value01',
                'key02' => 'value02',
            ],
            'mixin02' => [
                'key01' => 'value01',
                'key02' => 'value02',
            ],
            'mixin03' => [
                'key03' => 'value03',
            ],
            'klaus01' => [
                'key01' => 'value01 override 01',
                'key02' => 'value02',
            ],
            'klaus02' => [
                'key01' => 'value01 override 02',
                'key02' => [
                    'horst01' => 'gerda01',
                ],
                'key03' => [
                    'key01' => 'value01',
                    'key02' => null,
                ],
            ],
            'klaus03' => [
                'key01' => 'value01 override 02',
                'key02' => [
                    'horst01' => 'gerda01',
                ],
                'key03' => [
                    'key01' => 'value01',
                    'key02' => null,
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration());
    }

    #[Test]
    public function getResolvedConfigurationThrowsExceptionIfCycleDependenciesOnSameLevelIsFound(): void
    {
        $input = [
            'someKey' => [
                '__inheritances' => [
                    10 => 'TYPO3.CMS.Form.anotherKey',
                ],
            ],
            'anotherKey' => [
                '__inheritances' => [
                    10 => 'TYPO3.CMS.Form.someKey',
                ],
            ],
        ];

        $this->expectException(CycleInheritancesException::class);
        $this->expectExceptionCode(1474900797);

        $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration();
    }

    #[Test]
    public function getResolvedConfigurationThrowsExceptionIfCycleDependenciesOnSameLevelWithGapIsFound(): void
    {
        $input = [
            'klaus1' => [
                '__inheritances' => [
                    10 => 'TYPO3.CMS.Form.klaus2',
                ],
            ],
            'klaus2' => [
                '__inheritances' => [
                    10 => 'TYPO3.CMS.Form.mixin1',
                ],
            ],
            'mixin1' => [
                '__inheritances' => [
                    10 => 'TYPO3.CMS.Form.mixin2',
                ],
            ],
            'mixin2' => [
                '__inheritances' => [
                    10 => 'TYPO3.CMS.Form.klaus2',
                ],
            ],
        ];

        $this->expectException(CycleInheritancesException::class);
        $this->expectExceptionCode(1474900799);

        $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration();
    }

    #[Test]
    public function getResolvedConfigurationThrowsExceptionIfCycleDependenciesOnHigherLevelIsFound(): void
    {
        $input = [
            'klaus1' => [
                'key01' => 'value',
                'key02' => [
                    '__inheritances' => [
                        10 => 'TYPO3.CMS.Form.mixin01',
                    ],
                ],
            ],
            'klaus2' => [
                '__inheritances' => [
                    10 => 'TYPO3.CMS.Form.klaus1',
                ],
                'key02' => [
                    '__inheritances' => [
                        10 => 'TYPO3.CMS.Form.mixin01',
                        20 => 'TYPO3.CMS.Form.mixin02',
                    ],
                ],
            ],
            'mixin01' => [
                'liselotte01' => 'value',
            ],
            'mixin02' => [
                '__inheritances' => [
                    10 => 'TYPO3.CMS.Form.klaus2',
                ],
                'liselotte02' => 'value',
            ],
        ];

        $this->expectException(CycleInheritancesException::class);
        $this->expectExceptionCode(1474900797);

        $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration();
    }
}
