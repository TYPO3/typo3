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
use TYPO3\CMS\Backend\Form\FormDataGroup\FlexFormSegment;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaFlexProcessTest extends UnitTestCase
{
    /**
     * @var BackendUserAuthentication|ObjectProphecy
     */
    protected $backendUserProphecy;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var BackendUserAuthentication|ObjectProphecy backendUserProphecy */
        $this->backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $this->backendUserProphecy->reveal();
        $GLOBALS['BE_USER']->groupData['non_exclude_fields'] = '';

        // Some tests call FormDataCompiler for sub elements. Those tests have functional test characteristics.
        // This is ok for the time being, but this settings takes care only parts of the compiler are called
        // to have less dependencies.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [];
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionWithMissingDataStructureIdentifier()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1480765571);
        (new TcaFlexProcess())->addData($input);
    }

    /**
     * @test
     */
    public function addDataRemovesSheetIfDisabledByPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'aSheet' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ]
                                            ],
                                        ],
                                    ],
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
                            'aFlex.' => [
                                'aSheet.' => [
                                    'disabled' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [],
        ];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsSheetTitleFromPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'selectTreeCompileItems' => false,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'aSheet' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ]
                                            ],
                                        ],
                                    ],
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
                            'aFlex.' => [
                                'aSheet.' => [
                                    'sheetTitle' => 'aTitle',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'aSheet' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'sheetTitle' => 'aTitle',
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsSheetDescriptionFromPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'selectTreeCompileItems' => false,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'aSheet' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ]
                                            ],
                                        ],
                                    ],
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
                            'aFlex.' => [
                                'aSheet.' => [
                                    'sheetDescription' => 'aDescription',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'aSheet' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'sheetDescription' => 'aDescription',
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsSheetShortDescriptionFromPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'selectTreeCompileItems' => false,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'aSheet' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ]
                                            ],
                                        ],
                                    ],
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
                            'aFlex.' => [
                                'aSheet.' => [
                                    'sheetDescription' => 'sheetShortDescr',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'aSheet' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'sheetDescription' => 'sheetShortDescr',
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsSheetShortDescriptionForSingleSheetFromPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'selectTreeCompileItems' => false,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ]
                                            ],
                                        ],
                                    ],
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
                            'aFlex.' => [
                                'sDEF.' => [
                                    'sheetDescription' => 'sheetShortDescr',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'sheetDescription' => 'sheetShortDescr',
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesExcludeFieldFromDataStructure()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'selectTreeCompileItems' => false,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'exclude' => '1',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ]
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        $this->backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(false);
        $GLOBALS['BE_USER']->groupData['non_exclude_fields'] = '';

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [],
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsExcludeFieldInDataStructureWithUserAccess()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'selectTreeCompileItems' => false,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'exclude' => '1',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ]
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        $this->backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(false);
        $GLOBALS['BE_USER']->groupData['non_exclude_fields'] = 'aTable:aField;aFlex;sDEF;aFlexField';

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                                'exclude' => '1',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsExcludeFieldInDataStructureForAdminUser()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'selectTreeCompileItems' => false,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'exclude' => '1',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ]
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        $this->backendUserProphecy->isAdmin()->shouldBeCalled()->willReturn(true);
        $GLOBALS['BE_USER']->groupData['non_exclude_fields'] = '';

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                                'exclude' => '1',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesPageTsDisabledFieldFromDataStructure()
    {
        $input = [
            'tableName' => 'aTable',
            'selectTreeCompileItems' => false,
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ]
                                            ],
                                        ],
                                    ],
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
                            'aFlex.' => [
                                'sDEF.' => [
                                    'aFlexField.' => [
                                        'disabled' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [],
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataHandlesPageTsConfigSettingsOfSingleFlexField()
    {
        $input = [
            'tableName' => 'aTable',
            'selectTreeCompileItems' => false,
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
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
                                    ],
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
                            'aFlex.' => [
                                'sDEF.' => [
                                    'aFlexField.' => [
                                        'altLabels.' => [
                                            '0' => 'labelOverride',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [
            TcaRadioItems::class => [],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(true);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds'] = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'radio',
                                    'items' => [
                                        0 => [
                                            0 => 'labelOverride',
                                            1 => 'aValue',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultValueFromFlexTcaForField()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'selectTreeCompileItems' => false,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'config' => [
                                                        'type' => 'input',
                                                        'default' => 'defaultValue',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [
            DatabaseRowDefaultValues::class => [],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(true);

        $expected = $input;
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['aFlexField']['vDEF'] = 'defaultValue';

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForDataStructureTypeArrayWithoutSection()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'type' => 'array',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(true);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1440685208);

        (new TcaFlexProcess())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForDataStructureSectionWithoutTypeArray()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'section' => '1',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(true);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1440685208);

        (new TcaFlexProcess())->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsValuesAndStructureForSectionContainerElements()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'selectTreeCompileItems' => false,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'section_1' => [
                                    'el' => [
                                        '1' => [
                                            'container_1' => [
                                                // It should set the default value for aFlexField here
                                                'el' => [
                                                ],
                                            ],
                                        ],
                                        '2' => [
                                            'container_1' => [
                                                'el' => [
                                                    'aFlexField' => [
                                                        // It should keep this value
                                                        'vDEF' => 'dbValue',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'meta' => [],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'section_1' => [
                                                    'section' => '1',
                                                    'type' => 'array',
                                                    'el' => [
                                                        'container_1' => [
                                                            'type' => 'array',
                                                            'el' => [
                                                                'aFlexField' => [
                                                                    'label' => 'aFlexFieldLabel',
                                                                    'config' => [
                                                                        'type' => 'input',
                                                                        'default' => 'defaultValue',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [
            DatabaseRowDefaultValues::class => [],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(true);

        $expected = $input;

        // A default value for existing container field aFlexField should have been set
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';

        // Data structure of given containers is copied over to "children" referencing the existing container name
        $expected['processedTca']['columns']['aField']['config']['ds']['sheets']['sDEF']['ROOT']['el']['section_1']['children']['1']
            =  $expected['processedTca']['columns']['aField']['config']['ds']['sheets']['sDEF']['ROOT']['el']['section_1']['el']['container_1'];
        $expected['processedTca']['columns']['aField']['config']['ds']['sheets']['sDEF']['ROOT']['el']['section_1']['children']['2']
            =  $expected['processedTca']['columns']['aField']['config']['ds']['sheets']['sDEF']['ROOT']['el']['section_1']['el']['container_1'];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForInlineElementsNestedInSectionContainers()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'section_1' => [
                                                    'section' => '1',
                                                    'type' => 'array',
                                                    'el' => [
                                                        'container_1' => [
                                                            'type' => 'array',
                                                            'el' => [
                                                                'aFlexField' => [
                                                                    'label' => 'aFlexFieldLabel',
                                                                    'config' => [
                                                                        'type' => 'inline',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1458745468);

        (new TcaFlexProcess())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForNestedSectionContainers()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'section_1' => [
                                                    'section' => '1',
                                                    'type' => 'array',
                                                    'el' => [
                                                        'container_1' => [
                                                            'type' => 'array',
                                                            'el' => [
                                                                'section_nested' => [
                                                                    'section' => '1',
                                                                    'type' => 'array',
                                                                    'el' => [
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1458745712);

        (new TcaFlexProcess())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForSelectElementsInSectionContainers()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'section_1' => [
                                                    'section' => '1',
                                                    'type' => 'array',
                                                    'el' => [
                                                        'container_1' => [
                                                            'type' => 'array',
                                                            'el' => [
                                                                'section_nested' => [
                                                                    'config' => [
                                                                        'type' => 'select',
                                                                        'MM' => '',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1481647089);

        (new TcaFlexProcess())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForGroupElementsInSectionContainers()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 1,
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'section_1' => [
                                                    'section' => '1',
                                                    'type' => 'array',
                                                    'el' => [
                                                        'container_1' => [
                                                            'type' => 'array',
                                                            'el' => [
                                                                'section_nested' => [
                                                                    'config' => [
                                                                        'type' => 'group',
                                                                        'MM' => '',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1481647089);

        (new TcaFlexProcess())->addData($input);
    }

    /**
     * @test
     */
    public function addDataCallsFlexFormSegmentGroupForFieldAndAddsFlexParentDatabaseRow()
    {
        $input = [
            'tableName' => 'aTable',
            'selectTreeCompileItems' => false,
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        /** @var FlexFormSegment|ObjectProphecy $dummyGroup */
        $dummyGroup = $this->prophesize(FlexFormSegment::class);
        GeneralUtility::addInstance(FlexFormSegment::class, $dummyGroup->reveal());

        // Check array given to flex group contains databaseRow as flexParentDatabaseRow and check compile is called
        $dummyGroup->compile(Argument::that(function ($result) use ($input) {
            if ($result['flexParentDatabaseRow'] === $input['databaseRow']) {
                return true;
            }
            return false;
        }))->shouldBeCalled()->willReturnArgument(0);

        (new TcaFlexProcess())->addData($input);
    }

    /**
     * @test
     */
    public function addDataCallsFlexFormSegmentGroupForDummyContainerAndAddsFlexParentDatabaseRow()
    {
        $input = [
            'tableName' => 'aTable',
            'selectTreeCompileItems' => false,
            'effectivePid' => 1,
            'databaseRow' => [
                'uid' => 5,
                'aField' => [
                    'data' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'dataStructureIdentifier' => '{"type":"tca","tableName":"aTable","fieldName":"aField","dataStructureKey":"aFlex"}',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [
                                            'type' => 'array',
                                            'el' => [
                                                'aFlexField' => [
                                                    'label' => 'aFlexFieldLabel',
                                                    'config' => [
                                                        'type' => 'input',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'pageTsConfig' => [],
        ];

        /** @var FlexFormSegment|ObjectProphecy $dummyGroupExisting */
        $dummyGroupExisting = $this->prophesize(FlexFormSegment::class);
        GeneralUtility::addInstance(FlexFormSegment::class, $dummyGroupExisting->reveal());
        // Check array given to flex group contains databaseRow as flexParentDatabaseRow and check compile is called
        $dummyGroupExisting->compile(Argument::that(function ($result) use ($input) {
            if ($result['flexParentDatabaseRow'] === $input['databaseRow']) {
                return true;
            }
            return false;
        }))->shouldBeCalled()->willReturnArgument(0);

        (new TcaFlexProcess())->addData($input);
    }
}
