<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Configuration;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\CycleInheritancesException;
use TYPO3\CMS\Form\Mvc\Configuration\InheritancesResolverService;

/**
 * Test case
 */
class InheritancesResolverServiceTest extends UnitTestCase
{
    /**
     * @var InheritancesResolverService
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new InheritancesResolverService();
    }

    /**
     * @test
     */
    public function getMergedConfigurationSimpleInheritance()
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
                        'key03' => 'value'
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

        $this->assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration($input));
    }

    /**
     * @test
     */
    public function getMergedConfigurationSimpleInheritanceOverrideValue()
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

        $this->assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration($input));
    }

    /**
     * @test
     */
    public function getMergedConfigurationSimpleInheritanceRemoveValue()
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

        $this->assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration($input));
    }

    /**
     * @test
     */
    public function getMergedConfigurationSimpleMixin()
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

        $this->assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration($input));
    }

    /**
     * @test
     */
    public function getMergedConfigurationAdvancedMixin()
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
                        'horst01' => 'gerda01'
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
                        'horst01' => 'gerda01'
                    ],
                    'key03' => [
                        'key01' => 'value01',
                        'key02' => null,
                    ],
                ],
                'klaus03' => [
                    'key01' => 'value01 override 02',
                    'key02' => [
                        'horst01' => 'gerda01'
                    ],
                    'key03' => [
                        'key01' => 'value01',
                        'key02' => null,
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration($input));
    }

    /**
     * @test
     */
    public function getResolvedConfigurationThrowsExceptionIfCycleDepenciesOnSameLevelIsFound()
    {
        $input = [
            'TYPO3' => [
                'CMS' => [
                    'Form' => [
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
                    ],
                ],
            ],
        ];

        $this->expectException(CycleInheritancesException::class);
        $this->expectExceptionCode(1474900797);

        $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration($input);
    }

    /**
     * @test
     */
    public function getResolvedConfigurationThrowsExceptionIfCycleDepenciesOnSameLevelWithGapIsFound()
    {
        $input = [
            'TYPO3' => [
                'CMS' => [
                    'Form' => [
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
                    ],
                ],
            ],
        ];

        $this->expectException(CycleInheritancesException::class);
        $this->expectExceptionCode(1474900799);

        $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration($input);
    }

    /**
     * @test
     */
    public function getResolvedConfigurationThrowsExceptionIfCycleDepenciesOnHigherLevelIsFound()
    {
        $input = [
            'TYPO3' => [
                'CMS' => [
                    'Form' => [
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
                    ],
                ],
            ],
        ];

        $this->expectException(CycleInheritancesException::class);
        $this->expectExceptionCode(1474900797);

        $this->subject->reset()->setReferenceConfiguration($input)->getResolvedConfiguration($input);
    }
}
