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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaCheckboxItemsTest extends UnitTestCase
{
    use ProphecyTrait;

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    public function checkboxConfigurationDataProvider(): array
    {
        return [
            'simpleCheckboxConfig' => [
                'input' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
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
                ],
                'expected' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'items' => [
                                        0 => [
                                            'foo', // @todo a followup patch should refactor towards 'label' => 'foo'
                                            'bar', // @todo a followup patch should remove this numeric key altogether
                                            'invertStateDisplay' => false
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'brokenSimpleCheckboxConfig' => [
                'input' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'baz'
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'invertStateDisplay' => false
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'toggleCheckboxConfig' => [
                'input' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled'
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'invertStateDisplay' => false
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'inverted_toggleCheckboxConfig' => [
                'input' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
                                            'invertStateDisplay' => true
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'invertStateDisplay' => true
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'labeledCheckboxConfig' => [
                'input' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxLabeledToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled'
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxLabeledToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
                                            'invertStateDisplay' => false
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'inverted_labeledCheckboxConfig' => [
                'input' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxLabeledToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
                                            'invertStateDisplay' => true
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxLabeledToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
                                            'invertStateDisplay' => true
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'iconCheckboxConfig' => [
                'input' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxIconToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
                                            'iconIdentifierChecked' => 'styleguide-icon-toggle-checked',
                                            'iconIdentifierUnchecked' => 'styleguide-icon-toggle-checked',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxIconToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'iconIdentifierChecked' => 'styleguide-icon-toggle-checked',
                                            'iconIdentifierUnchecked' => 'styleguide-icon-toggle-checked',
                                            'invertStateDisplay' => false
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'inverted_iconCheckboxConfig' => [
                'input' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxIconToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
                                            'iconIdentifierChecked' => 'styleguide-icon-toggle-checked',
                                            'iconIdentifierUnchecked' => 'styleguide-icon-toggle-checked',
                                            'invertStateDisplay' => true
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'tableName' => 'foo',
                    'processedTca' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'check',
                                    'renderType' => 'checkboxIconToggle',
                                    'items' => [
                                        0 => [
                                            'foo',
                                            'bar',
                                            'iconIdentifierChecked' => 'styleguide-icon-toggle-checked',
                                            'iconIdentifierUnchecked' => 'styleguide-icon-toggle-checked',
                                            'invertStateDisplay' => true
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider checkboxConfigurationDataProvider
     */
    public function addDataKeepExistingItems(array $input, array $expectedResult): void
    {
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        self::assertSame($expectedResult, (new TcaCheckboxItems())->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfItemsAreNoArray(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'check',
                            'items' => [
                                0 => 'aoeu',
                            ],
                        ],
                    ],
                ],
            ],
            'tableName' => 'foo'
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1440499337);
        (new TcaCheckboxItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfItemLabelIsNotSet(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'check',
                            'items' => [
                                0 => [
                                    'funnyKey' => 'funnyValue',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'tableName' => 'foo'
        ];

        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1440499338);
        (new TcaCheckboxItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataTranslatesItemLabels(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'check',
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
            'tableName' => 'foo'
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();

        $languageService->sL('aLabel')->shouldBeCalled()->willReturn('translated');

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'translated';
        $expected['processedTca']['columns']['aField']['config']['items'][0]['invertStateDisplay'] = false;

        self::assertSame($expected, (new TcaCheckboxItems())->addData($input));
    }

    /**
     * @test
     */
    public function addDataCallsItemsProcFunc(): void
    {
        $input = [
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => [],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'check',
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
            'type' => 'check',
            'items' => [
                'foo' => 'bar',
            ],
        ];
        self::assertSame($expected, (new TcaCheckboxItems())->addData($input));
    }

    /**
     * @test
     */
    public function addDataItemsProcFuncReceivesParameters(): void
    {
        $input = [
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => ['config' => 'someValue'],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
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
            'flexParentDatabaseRow' => [
                'aParentDatabaseRowFieldName' => 'aParentDatabaseRowFieldValue',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'check',
                            'aKey' => 'aValue',
                            'items' => [
                                0 => [
                                    0 => 'foo',
                                    1 => 'bar',
                                    'invertedStateDisplay' => false
                                ],
                            ],
                            'itemsProcFunc' => function (array $parameters, $pObj) {
                                if (
                                    $parameters['items'] !== [ 0 => [0=>'foo', 1 =>'bar', 'invertStateDisplay' => false]]
                                    || $parameters['config']['aKey'] !== 'aValue'
                                    || $parameters['TSconfig'] !== [ 'itemParamKey' => 'itemParamValue' ]
                                    || $parameters['table'] !== 'aTable'
                                    || $parameters['row'] !== [ 'aField' => 'aValue' ]
                                    || $parameters['field'] !== 'aField'
                                    || $parameters['flexParentDatabaseRow']['aParentDatabaseRowFieldName'] !== 'aParentDatabaseRowFieldValue'
                                    || $parameters['inlineParentUid'] !== 1
                                    || $parameters['inlineParentTableName'] !== 'aTable'
                                    || $parameters['inlineParentFieldName'] !== 'aField'
                                    || $parameters['inlineParentConfig'] !== ['config' => 'someValue']
                                    || $parameters['inlineTopMostParentUid'] !== 1
                                    || $parameters['inlineTopMostParentTableName'] !== 'topMostTable'
                                    || $parameters['inlineTopMostParentFieldName'] !== 'topMostField'
                                ) {
                                    throw new \UnexpectedValueException('broken', 1476109402);
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

        (new TcaCheckboxItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataItemsProcFuncEnqueuesFlashMessageOnException(): void
    {
        $input = [
            'tableName' => 'aTable',
            'inlineParentUid' => 1,
            'inlineParentTableName' => 'aTable',
            'inlineParentFieldName' => 'aField',
            'inlineParentConfig' => [],
            'inlineTopMostParentUid' => 1,
            'inlineTopMostParentTableName' => 'topMostTable',
            'inlineTopMostParentFieldName' => 'topMostField',
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
                            'type' => 'check',
                            'aKey' => 'aValue',
                            'items' => [
                                0 => [
                                    'foo',
                                    'bar',
                                ],
                            ],
                            'itemsProcFunc' => function (array $parameters, $pObj) {
                                throw new \UnexpectedValueException('anException', 1438604329);
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

        (new TcaCheckboxItems())->addData($input);
    }

    /**
     * @test
     */
    public function addDataTranslatesItemLabelsFromPageTsConfig(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'check',
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
        $expected['processedTca']['columns']['aField']['config']['items'][0]['invertStateDisplay'] = false;

        self::assertSame($expected, (new TcaCheckboxItems())->addData($input));
    }
}
