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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaCheckboxItemsTest extends UnitTestCase
{
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
                                            'invertStateDisplay' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                            'baz',
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
                                            'invertStateDisplay' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
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
                                            'invertStateDisplay' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
                                            'invertStateDisplay' => true,
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
                                            'invertStateDisplay' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
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
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
                                            'invertStateDisplay' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
                                            'invertStateDisplay' => true,
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
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
                                            'invertStateDisplay' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                            'iconIdentifierChecked' => 'styleguide-icon-toggle-checked',
                                            'iconIdentifierUnchecked' => 'styleguide-icon-toggle-checked',
                                            'invertStateDisplay' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                            'labelChecked' => 'Enabled',
                                            'labelUnchecked' => 'Disabled',
                                            'iconIdentifierChecked' => 'styleguide-icon-toggle-checked',
                                            'iconIdentifierUnchecked' => 'styleguide-icon-toggle-checked',
                                            'invertStateDisplay' => true,
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
                                            'iconIdentifierChecked' => 'styleguide-icon-toggle-checked',
                                            'iconIdentifierUnchecked' => 'styleguide-icon-toggle-checked',
                                            'invertStateDisplay' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider checkboxConfigurationDataProvider
     */
    public function addDataKeepExistingItems(array $input, array $expectedResult): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

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
            'tableName' => 'foo',
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

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
            'tableName' => 'foo',
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

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
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'tableName' => 'foo',
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;

        $languageService->expects(self::atLeastOnce())->method('sL')->with('aLabel')->willReturn('translated');

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
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                $parameters['items'] = [
                                    'foo' => 'bar',
                                ];
                            },
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

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
                        ],
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
                                    'invertedStateDisplay' => false,
                                ],
                            ],
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                if (
                                    $parameters['items'] !== [ 0 => [0 => 'foo', 'invertStateDisplay' => false]]
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

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);
        $flashMessage = $this->createMock(FlashMessage::class);
        GeneralUtility::addInstance(FlashMessage::class, $flashMessage);
        $flashMessageService = $this->createMock(FlashMessageService::class);
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService);
        $flashMessageQueue = $this->createMock(FlashMessageQueue::class);
        $flashMessageService->method('getMessageQueueByIdentifier')->with(self::anything())->willReturn($flashMessageQueue);

        // itemsProcFunc must NOT have raised an exception
        $flashMessageQueue->expects(self::never())->method('enqueue');

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
                        ],
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
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                throw new \UnexpectedValueException('anException', 1438604329);
                            },
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->with(self::anything())->willReturn('');
        $GLOBALS['LANG'] = $languageService;
        $flashMessage = $this->createMock(FlashMessage::class);
        GeneralUtility::addInstance(FlashMessage::class, $flashMessage);
        $flashMessageService = $this->createMock(FlashMessageService::class);
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService);
        $flashMessageQueue = $this->createMock(FlashMessageQueue::class);
        $flashMessageService->method('getMessageQueueByIdentifier')->with(self::anything())->willReturn($flashMessageQueue);

        $flashMessageQueue->expects(self::atLeastOnce())->method('enqueue');

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
                        ],
                    ],
                ],
            ],
        ];

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->expects(self::atLeastOnce())->method('sL')->withConsecutive(['aLabel'], ['labelOverride'])->willReturnArgument(0);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['items'][0][0] = 'labelOverride';
        $expected['processedTca']['columns']['aField']['config']['items'][0]['invertStateDisplay'] = false;

        self::assertSame($expected, (new TcaCheckboxItems())->addData($input));
    }
}
