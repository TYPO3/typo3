<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataGroup\FlexFormSegment;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case
 */
class TcaFlexProcessTest extends UnitTestCase
{
    /**
     * @var TcaFlexProcess
     */
    protected $subject;

    /**
     * @var BackendUserAuthentication|ObjectProphecy
     */
    protected $backendUserProphecy;

    protected function setUp()
    {
        /** @var BackendUserAuthentication|ObjectProphecy backendUserProphecy */
        $this->backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $this->backendUserProphecy->reveal();
        $GLOBALS['BE_USER']->groupData['non_exclude_fields'] = '';

        // Some tests call FormDataCompiler for sub elements. Those tests have functional test characteristics.
        // This is ok for the time being, but this settings takes care only parts of the compiler are called
        // to have less dependencies.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [];

        $this->subject = new TcaFlexProcess();
    }

    /**
     * @test
     */
    public function addDataRemovesSheetIfDisabledByPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
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
            'meta' => [
                'dataStructurePointers' => [
                    'pointerField' => 'aFlex',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsSheetTitleFromPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
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
            'meta' => [
                'dataStructurePointers' => [
                    'pointerField' => 'aFlex',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsSheetDescriptionFromPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
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
            'meta' => [
                'dataStructurePointers' => [
                    'pointerField' => 'aFlex',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsSheetShortDescriptionFromPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
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
            'meta' => [
                'dataStructurePointers' => [
                    'pointerField' => 'aFlex',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsSheetShortDescriptionForSingleSheetFromPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
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
            'meta' => [
                'dataStructurePointers' => [
                    'pointerField' => 'aFlex',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesExcludeFieldFromDataStructure()
    {
        $input = [
            'tableName' => 'aTable',
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
            'meta' => [
                'dataStructurePointers' => [
                    'pointerField' => 'aFlex',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsExcludeFieldInDataStructureWithUserAccess()
    {
        $input = [
            'tableName' => 'aTable',
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
            'meta' => [
                'dataStructurePointers' => [
                    'pointerField' => 'aFlex',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsExcludeFieldInDataStructureForAdminUser()
    {
        $input = [
            'tableName' => 'aTable',
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
            'meta' => [
                'dataStructurePointers' => [
                    'pointerField' => 'aFlex',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesPageTsDisabledFieldFromDataStructure()
    {
        $input = [
            'tableName' => 'aTable',
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
            'meta' => [
                'dataStructurePointers' => [
                    'pointerField' => 'aFlex',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataHandlesPageTsConfigSettingsOfSingleFlexField()
    {
        $input = [
            'tableName' => 'aTable',
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
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class => [],
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
            'meta' => [
                'dataStructurePointers' => [
                    'pointerField' => 'aFlex',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultValueFromFlexTcaForField()
    {
        $input = [
            'tableName' => 'aTable',
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
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [],
        ];

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess(Argument::cetera())->willReturn(true);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [
            'dataStructurePointers' => [
                'pointerField' => 'aFlex'
            ],
        ];
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['aFlexField']['vDEF'] = 'defaultValue';

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForDataStructureTypeArrayWithoutSection()
    {
        $input = [
            'tableName' => 'aTable',
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

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1440685208);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForDataStructureSectionWithoutTypeArray()
    {
        $input = [
            'tableName' => 'aTable',
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

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1440685208);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsValuesAndStructureForSectionContainerElements()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
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
                            'lEN' => [
                                'section_1' => [
                                    'el' => [
                                        '1' => [
                                            'container_1' => [
                                                // It should add the default value for aFlexField here
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
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues::class => [],
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

        // Dummy row values for container_1 on lDEF sheet
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['templateRows']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForInlineElementsNestedInSectionContainers()
    {
        $input = [
            'tableName' => 'aTable',
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

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1458745468);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForNestedSectionContainers()
    {
        $input = [
            'tableName' => 'aTable',
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

        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1458745712);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataCallsFlexFormSegmentGroupForFieldAndAddsFlexParentDatabaseRow()
    {
        $input = [
            'tableName' => 'aTable',
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

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataCallsFlexFormSegmentGroupForDummyContainerAndAddsFlexParentDatabaseRow()
    {
        $input = [
            'tableName' => 'aTable',
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

        $this->subject->addData($input);
    }

    /**
     * Test for the deprecated "flexHack" pageTsConfig transition, verifies that
     * all three PAGE_TSCONFIG_ID, PAGE_TSCONFIG_IDLIST and PAGE_TSCONFIG_STR
     * are hand over to the flex field compiler.
     *
     * @test
     */
    public function addDataHandsPageTsConfigIdOverToFlexFormSegmentGroupAsFlexHack()
    {
        $input = [
            'tableName' => 'aTable',
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
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'PAGE_TSCONFIG_ID' => '42',
                            'PAGE_TSCONFIG_IDLIST' => '2,3,5',
                            'PAGE_TSCONFIG_STR' => 'configString',
                        ],
                    ],
                ],
            ],
        ];

        /** @var FlexFormSegment|ObjectProphecy $dummyGroupExisting */
        $dummyGroupExisting = $this->prophesize(FlexFormSegment::class);
        GeneralUtility::addInstance(FlexFormSegment::class, $dummyGroupExisting->reveal());
        // Check array given to flex group contains pageTsConfig with flexHack field
        $dummyGroupExisting->compile(Argument::that(function ($result) use ($input) {
            if ($result['pageTsConfig']['flexHack.'] === $input['pageTsConfig']['TCEFORM.']['aTable.']['aField.']) {
                return true;
            }
            return false;
        }))->shouldBeCalled()->willReturnArgument(0);

        $this->subject->addData($input);
    }
}
