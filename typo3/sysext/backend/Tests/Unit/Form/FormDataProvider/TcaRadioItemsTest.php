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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaRadioItemsTest extends UnitTestCase
{
    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfRadioItemsNotDefined()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438594829);
        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataKeepExistingItems()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    'foo',
                                    'bar',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        self::assertSame($expected, (new TcaRadioItems())->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfItemsAreNoArray()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => 'aoeu',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438607163);
        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfItemLabelIsNotSet()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    'funnyKey' => 'funnyValue',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438607164);
        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfItemValueIsNotSet()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1438607165);
        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataTranslatesItemLabels()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                    1 => 'aValue',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();

        $languageService->sL('aLabel')->shouldBeCalled()->willReturn('translated');

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'translated';

        self::assertSame($expected, (new TcaRadioItems())->addData($input));
        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataCallsItemsProcFunc()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [],
                            'itemsProcFunc' => function (array $parameters, $pObj) {
                                $parameters['items'] = [
                                    'foo' => 'bar',
                                ];
                            },
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config'] = [
            'type' => 'radio',
            'items' => [
                'foo' => 'bar',
            ],
        ];
        self::assertSame($expected, (new TcaRadioItems())->addData($input));
    }

    /**
     * @test
     */
    public function addDataItemsProcFuncReceivesParameters()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'itemsProcFunc.' => [
                                'itemParamKey' => 'itemParamValue',
                            ],
                        ]
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'aKey' => 'aValue',
                            'items' => [
                                0 => [
                                    'foo',
                                    'bar',
                                ],
                            ],
                            'itemsProcFunc' => function (array $parameters, $pObj) {
                                if ($parameters['items'] !== [ 0 => [ 'foo', 'bar'] ]
                                    || $parameters['config']['aKey'] !== 'aValue'
                                    || $parameters['TSconfig'] !== [ 'itemParamKey' => 'itemParamValue' ]
                                    || $parameters['table'] !== 'aTable'
                                    || $parameters['row'] !== [ 'aField' => 'aValue' ]
                                    || $parameters['field'] !== 'aField'
                                ) {
                                    throw new \UnexpectedValueException('broken', 1476109434);
                                }
                            },
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);
        /** @var FlashMessage|ObjectProphecy $flashMessage */
        $flashMessage = $this->prophesize(FlashMessage::class);
        GeneralUtility::addInstance(FlashMessage::class, $flashMessage->reveal());
        /** @var FlashMessageService|ObjectProphecy $flashMessageService */
        $flashMessageService = $this->prophesize(FlashMessageService::class);
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService->reveal());
        /** @var FlashMessageQueue|ObjectProphecy $flashMessageQueue */
        $flashMessageQueue = $this->prophesize(FlashMessageQueue::class);
        $flashMessageService->getMessageQueueByIdentifier(Argument::cetera())->willReturn($flashMessageQueue->reveal());

        // itemsProcFunc must NOT have raised an exception
        $flashMessageQueue->enqueue($flashMessage)->shouldNotBeCalled();

        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataItemsProcFuncEnqueuesFlashMessageOnException()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => 'aValue',
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'itemsProcFunc.' => [
                                'itemParamKey' => 'itemParamValue',
                            ],
                        ]
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'aKey' => 'aValue',
                            'items' => [
                                0 => [
                                    'foo',
                                    'bar',
                                ],
                            ],
                            'itemsProcFunc' => function (array $parameters, $pObj) {
                                throw new \UnexpectedValueException('anException', 1476109435);
                            },
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        /** @var FlashMessage|ObjectProphecy $flashMessage */
        $flashMessage = $this->prophesize(FlashMessage::class);
        GeneralUtility::addInstance(FlashMessage::class, $flashMessage->reveal());
        /** @var FlashMessageService|ObjectProphecy $flashMessageService */
        $flashMessageService = $this->prophesize(FlashMessageService::class);
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService->reveal());
        /** @var FlashMessageQueue|ObjectProphecy $flashMessageQueue */
        $flashMessageQueue = $this->prophesize(FlashMessageQueue::class);
        $flashMessageService->getMessageQueueByIdentifier(Argument::cetera())->willReturn($flashMessageQueue->reveal());

        $flashMessageQueue->enqueue($flashMessage)->shouldBeCalled();

        (new TcaRadioItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataTranslatesItemLabelsFromPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [
                                0 => [
                                    0 => 'aLabel',
                                    1 => 'aValue',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'altLabels.' => [
                                0 => 'labelOverride',
                            ],
                        ]
                    ],
                ],
            ],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL('aLabel')->willReturnArgument(0);

        $languageService->sL('labelOverride')->shouldBeCalled()->willReturnArgument(0);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'labelOverride';

        self::assertSame($expected, (new TcaRadioItems())->addData($input));
        (new TcaRadioItems())->addData($input);
    }
}
