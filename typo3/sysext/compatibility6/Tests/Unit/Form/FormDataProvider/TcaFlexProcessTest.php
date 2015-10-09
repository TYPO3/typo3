<?php
namespace TYPO3\CMS\Compatibility6\Tests\Unit\Form\FormDataProvider;

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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Compatibility6\Form\FormDataProvider\TcaFlexProcess;
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
    public function addDataOverwritesDataStructureLangDisableIfSetViaPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [],
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
                                'langDisable' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [
            'availableLanguageCodes' => [],
            'langDisable' => true,
            'langChildren' => false,
            'languagesOnSheetLevel' => [],
            'languagesOnElement' => [
                0 => 'DEF',
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataOverwritesDataStructureLangChildrenIfSetViaPageTsConfig()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'ds' => [
                                'sheets' => [
                                    'sDEF' => [
                                        'ROOT' => [],
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
                                'langChildren' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [
            'availableLanguageCodes' => [],
            'langDisable' => false,
            'langChildren' => false,
            'languagesOnSheetLevel' => [],
            'languagesOnElement' => [
                0 => 'DEF',
            ]
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
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
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [],
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
            'meta' => [
                'availableLanguageCodes' => [],
                'langDisable' => false,
                'langChildren' => false,
                'languagesOnSheetLevel' => [],
                'languagesOnElement' => [
                    0 => 'DEF',
                ]
            ],
            'sheets' => [],
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
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [],
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
            'meta' => [
                'availableLanguageCodes' => [],
                'langDisable' => false,
                'langChildren' => false,
                'languagesOnSheetLevel' => [],
                'languagesOnElement' => [
                    0 => 'DEF',
                ]
            ],
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
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [],
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
            'meta' => [
                'availableLanguageCodes' => [],
                'langDisable' => false,
                'langChildren' => false,
                'languagesOnSheetLevel' => [],
                'languagesOnElement' => [
                    0 => 'DEF',
                ]
            ],
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
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [],
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
            'meta' => [
                'availableLanguageCodes' => [],
                'langDisable' => false,
                'langChildren' => false,
                'languagesOnSheetLevel' => [],
                'languagesOnElement' => [
                    0 => 'DEF',
                ]
            ],
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
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [],
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
            'meta' => [
                'availableLanguageCodes' => [],
                'langDisable' => false,
                'langChildren' => false,
                'languagesOnSheetLevel' => [],
                'languagesOnElement' => [
                    0 => 'DEF',
                ]
            ],
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
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [],
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
            'meta' => [
                'availableLanguageCodes' => [],
                'langDisable' => false,
                'langChildren' => false,
                'languagesOnSheetLevel' => [],
                'languagesOnElement' => [
                    0 => 'DEF',
                ]
            ],
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [],
                    ],
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
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [],
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
            'meta' => [
                'availableLanguageCodes' => [],
                'langDisable' => false,
                'langChildren' => false,
                'languagesOnSheetLevel' => [],
                'languagesOnElement' => [
                    0 => 'DEF',
                ]
            ],
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
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [],
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
            'meta' => [
                'availableLanguageCodes' => [],
                'langDisable' => false,
                'langChildren' => false,
                'languagesOnSheetLevel' => [],
                'languagesOnElement' => [
                    0 => 'DEF',
                ]
            ],
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
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [],
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
            'meta' => [
                'availableLanguageCodes' => [],
                'langDisable' => false,
                'langChildren' => false,
                'languagesOnSheetLevel' => [],
                'languagesOnElement' => [
                    0 => 'DEF',
                ]
            ],
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataCleansLanguageDisabledDataValues()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'input_1' => [
                                    'vDEF' => 'input1 text',
                                    'vDEF.vDEFbase' => 'base',
                                    '_TRANSFORM_vDEF.vDEFbase' => 'transform',
                                    'vRemoveMe' => 'removeMe',
                                    'vRemoveMe.vDEFbase' => 'removeMe',
                                    '_TRANSFORM_vRemoveMe.vDEFbase' => 'removeMe',
                                ],
                                'section_1' => [
                                    'el' => [
                                        '1' => [
                                            'container_1' => [
                                                'el' => [
                                                    'input_2' => [
                                                        'vDEF' => 'input2 text',
                                                        'vRemoveMe' => 'removeMe',
                                                    ]
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'invalid1' => 'keepMe',
                                'invalid2' => [
                                    'el' => [
                                        '1' => [
                                            'keepMe',
                                        ],
                                    ],
                                ],
                                'invalid3' => [
                                    'el' => [
                                        '1' => [
                                            'container_2' => 'keepMe',
                                        ],
                                    ],
                                ],
                                'invalid4' => [
                                    'el' => [
                                        '1' => [
                                            'container_2' => [
                                                'el' => 'keepMe',
                                            ],
                                        ],
                                    ],
                                ],
                                'invalid5' => [
                                    'el' => [
                                        '1' => [
                                            'container_2' => [
                                                'el' => [
                                                    'field' => 'keepMe',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'lRemoveMe' => [],
                        ],
                    ],
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'title' => 'aLanguageTitle',
                    'iso' => 'DEF',
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'ds' => [
                                'meta' => [
                                    'langDisable' => 1,
                                ],
                                'sheets' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(true);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [
            'availableLanguageCodes' => [
                0 => 'DEF',
            ],
            'langDisable' => true,
            'langChildren' => false,
            'languagesOnSheetLevel' => [
                0 => 'DEF',
            ],
            'languagesOnElement' => [
                0 => 'DEF',
            ]
        ];

        unset($expected['databaseRow']['aField']['data']['sDEF']['lRemoveMe']);
        unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['input_1']['vRemoveMe']);
        unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['input_1']['vRemoveMe.vDEFbase']);
        unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['input_1']['_TRANSFORM_vRemoveMe.vDEFbase']);
        unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['input_2']['vRemoveMe']);

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesDataValuesIfUserHasNoAccess()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'input_1' => [
                                    'vDEF' => 'input1 text',
                                ],
                                'section_1' => [
                                    'el' => [
                                        '1' => [
                                            'container_1' => [
                                                'el' => [
                                                    'input_2' => [
                                                        'vDEF' => 'input2 text',
                                                        'vNoAccess' => 'removeMe',
                                                    ]
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'lNoAccess' => [],
                        ],
                    ],
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
                1 => [
                    'uid' => 1,
                    'iso' => 'NoAccess',
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'ds' => [
                                'meta' => [
                                    'langDisable' => 0,
                                ],
                                'sheets' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('1')->shouldBeCalled()->willReturn(false);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [
            'availableLanguageCodes' => [
                0 => 'DEF',
            ],
            'langDisable' => false,
            'langChildren' => false,
            'languagesOnSheetLevel' => [
                0 => 'DEF',
            ],
            'languagesOnElement' => [
                0 => 'DEF',
            ]
        ];

        unset($expected['databaseRow']['aField']['data']['sDEF']['lNoAccess']);
        unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['input_2']['vNoAccess']);

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataAddsNewLanguageDataValues()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'input_1' => [
                                    'vDEF' => 'input1 text',
                                ],
                            ],
                        ],
                    ],
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
                1 => [
                    'uid' => 1,
                    'iso' => 'EN',
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'ds' => [
                                'meta' => [
                                    'langDisable' => 0,
                                ],
                                'sheets' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('1')->shouldBeCalled()->willReturn(true);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [
            'availableLanguageCodes' => [
                0 => 'DEF',
                1 => 'EN',
            ],
            'langDisable' => false,
            'langChildren' => false,
            'languagesOnSheetLevel' => [
                0 => 'DEF',
                1 => 'EN',
            ],
            'languagesOnElement' => [
                0 => 'DEF',
            ]
        ];

        $expected['databaseRow']['aField']['data']['sDEF']['lEN'] = [];

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesDataValuesIfPageOverlayCheckIsEnabled()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [],
                            'lEN' => [],
                            'lNoOverlay' => [],
                        ],
                    ],
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
                1 => [
                    'uid' => 1,
                    'iso' => 'EN',
                ],
                2 => [
                    'uid' => 2,
                    'iso' => 'NoOverlay',
                ],
            ],
            'userTsConfig' => [
                'options.' => [
                    'checkPageLanguageOverlay' => '1',
                ],
            ],
            'pageLanguageOverlayRows' => [
                0 => [
                    'uid' => 1,
                    'sys_language_uid' => 1,
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'ds' => [
                                'meta' => [
                                    'langDisable' => 0,
                                ],
                                'sheets' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('1')->shouldBeCalled()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('2')->shouldBeCalled()->willReturn(true);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [
            'availableLanguageCodes' => [
                0 => 'DEF',
                1 => 'EN',
            ],
            'langDisable' => false,
            'langChildren' => false,
            'languagesOnSheetLevel' => [
                0 => 'DEF',
                1 => 'EN',
            ],
            'languagesOnElement' => [
                0 => 'DEF',
            ]
        ];

        unset($expected['databaseRow']['aField']['data']['sDEF']['lNoOverlay']);

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesLanguageDataValuesIfUserHasNoAccessWithLangChildren()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'input_1' => [
                                    'vDEF' => 'input1 text',
                                    'vNoAccess' => 'removeMe',
                                ],
                                'section_1' => [
                                    'el' => [
                                        '1' => [
                                            'container_1' => [
                                                'el' => [
                                                    'input_2' => [
                                                        'vDEF' => 'input2 text',
                                                        'vNoAccess' => 'removeMe',
                                                    ]
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'lNoAccess' => [],
                        ],
                    ],
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
                1 => [
                    'uid' => 1,
                    'iso' => 'NoAccess',
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'ds' => [
                                'meta' => [
                                    'langDisable' => 0,
                                    'langChildren' => 1,
                                ],
                                'sheets' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('1')->shouldBeCalled()->willReturn(false);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [
            'availableLanguageCodes' => [
                0 => 'DEF',
            ],
            'langDisable' => false,
            'langChildren' => true,
            'languagesOnSheetLevel' => [
                0 => 'DEF',
            ],
            'languagesOnElement' => [
                0 => 'DEF',
            ]
        ];

        unset($expected['databaseRow']['aField']['data']['sDEF']['lNoAccess']);
        unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['input_1']['vNoAccess']);
        unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['input_2']['vNoAccess']);

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRemovesDataValuesIfPageOverlayCheckIsEnabledWithLangChildren()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'input_1' => [
                                    'vDEF' => 'input1 text',
                                    'vEN' => 'input1 en text',
                                    'vNoOverlay' => 'removeMe',
                                ],
                            ],
                        ],
                    ],
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
                1 => [
                    'uid' => 1,
                    'iso' => 'EN',
                ],
                2 => [
                    'uid' => 2,
                    'iso' => 'NoOverlay',
                ],
            ],
            'userTsConfig' => [
                'options.' => [
                    'checkPageLanguageOverlay' => '1',
                ],
            ],
            'pageLanguageOverlayRows' => [
                0 => [
                    'uid' => 1,
                    'sys_language_uid' => 1,
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'ds' => [
                                'meta' => [
                                    'langDisable' => 0,
                                    'langChildren' => 1,
                                ],
                                'sheets' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->backendUserProphecy->isAdmin()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('0')->shouldBeCalled()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('1')->shouldBeCalled()->willReturn(true);
        $this->backendUserProphecy->checkLanguageAccess('2')->shouldBeCalled()->willReturn(true);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [
            'availableLanguageCodes' => [
                0 => 'DEF',
                1 => 'EN',
            ],
            'langDisable' => false,
            'langChildren' => true,
            'languagesOnSheetLevel' => [
                0 => 'DEF',
            ],
            'languagesOnElement' => [
                0 => 'DEF',
                1 => 'EN',
            ]
        ];

        unset($expected['databaseRow']['aField']['data']['sDEF']['lDEF']['input_1']['vNoOverlay']);

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
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'ds' => [
                                'meta' => [
                                    'langDisable' => 0,
                                    'langChildren' => 0,
                                ],
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
            'meta' => [
                'availableLanguageCodes' => [
                    0 => 'DEF',
                ],
                'langDisable' => false,
                'langChildren' => false,
                'languagesOnSheetLevel' => [
                    0 => 'DEF',
                ],
                'languagesOnElement' => [
                    0 => 'DEF',
                ]
            ],
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
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
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
            'availableLanguageCodes' => [
                0 => 'DEF',
            ],
            'langDisable' => false,
            'langChildren' => false,
            'languagesOnSheetLevel' => [
                0 => 'DEF',
            ],
            'languagesOnElement' => [
                0 => 'DEF',
            ]
        ];

        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['aFlexField']['vDEF'] = 'defaultValue';

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultValueFromFlexTcaForFieldInLocalizedSheet()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
                1 => [
                    'uid' => 1,
                    'iso' => 'EN',
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
            'availableLanguageCodes' => [
                0 => 'DEF',
                1 => 'EN',
            ],
            'langDisable' => false,
            'langChildren' => false,
            'languagesOnSheetLevel' => [
                0 => 'DEF',
                1 => 'EN',
            ],
            'languagesOnElement' => [
                0 => 'DEF'
            ]
        ];

        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['aFlexField']['vDEF'] = 'defaultValue';
        $expected['databaseRow']['aField']['data']['sDEF']['lEN']['aFlexField']['vDEF'] = 'defaultValue';

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultValueFromFlexTcaForFieldInLocalizedSheetWithLangChildren()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [],
                    'meta' => [],
                ],
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
                1 => [
                    'uid' => 1,
                    'iso' => 'EN',
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'meta' => [
                                    'langChildren' => 1,
                                ],
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
            'availableLanguageCodes' => [
                0 => 'DEF',
                1 => 'EN',
            ],
            'langDisable' => false,
            'langChildren' => true,
            'languagesOnSheetLevel' => [
                0 => 'DEF',
            ],
            'languagesOnElement' => [
                0 => 'DEF',
                1 => 'EN'
            ]
        ];

        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['aFlexField']['vDEF'] = 'defaultValue';
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['aFlexField']['vEN'] = 'defaultValue';

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
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
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
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
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
    public function addDataSetsValuesAndStructureForSectionContainerElementsNoLangChildren()
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
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
                1 => [
                    'uid' => 1,
                    'iso' => 'EN',
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
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [
            'availableLanguageCodes' => [
                0 => 'DEF',
                1 => 'EN',
            ],
            'langDisable' => false,
            'langChildren' => false,
            'languagesOnSheetLevel' => [
                0 => 'DEF',
                1 => 'EN',
            ],
            'languagesOnElement' => [
                0 => 'DEF',
            ]
        ];

        // A default value for existing container field aFlexField should have been set
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';
        // Also for the other defined language
        $expected['databaseRow']['aField']['data']['sDEF']['lEN']['section_1']['el']['1']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';

        // Dummy row values for container_1 on lDEF sheet
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['templateRows']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';
        // Dummy row values for container_1 on lDEF sheet
        $expected['databaseRow']['aField']['data']['sDEF']['lEN']['section_1']['templateRows']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';

        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsValuesAndStructureForSectionContainerElementsWithLangChildren()
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
                                                // It should set a default for both vDEF and vEN
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
                                                        // It should set a default for vEN
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
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
                1 => [
                    'uid' => 1,
                    'iso' => 'EN',
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds' => [
                                'meta' => [
                                    'langChildren' => 1,
                                ],
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
        $expected['processedTca']['columns']['aField']['config']['ds']['meta'] = [
            'availableLanguageCodes' => [
                0 => 'DEF',
                1 => 'EN',
            ],
            'langDisable' => false,
            'langChildren' => true,
            'languagesOnSheetLevel' => [
                0 => 'DEF',
            ],
            'languagesOnElement' => [
                0 => 'DEF',
                1 => 'EN',
            ]
        ];

        // A default value for existing container field aFlexField should have been set
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['1']['container_1']['el']['aFlexField']['vEN'] = 'defaultValue';
        // Also for the other defined language
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['el']['2']['container_1']['el']['aFlexField']['vEN'] = 'defaultValue';

        // There should be a templateRow for container_1 with defaultValue set for both languages
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['templateRows']['container_1']['el']['aFlexField']['vDEF'] = 'defaultValue';
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['section_1']['templateRows']['container_1']['el']['aFlexField']['vEN'] = 'defaultValue';

        $this->assertEquals($expected, $this->subject->addData($input));
    }


    /**
     * Date provider for addDataSetsLanguageFlags
     *
     * @return array
     */
    public function addDataSetsLanguageFlagsDataProvider()
    {
        return [
            'Default values are set' => [
                'sheets' => [
                    'sDEF' => [
                        'ROOT' => [],
                    ],
                ],
                false,
                false,
                [],
                [0 => 'DEF'],
            ],
            'langDisable is set to FALSE' => [
                'sheets' => [
                    'meta' => [
                        'langDisable' => 0,
                    ],
                    'sDEF' => [
                        'ROOT' => [],
                    ],
                ],
                false,
                false,
                [],
                [0 => 'DEF'],
            ],
            'langDisable is set to TRUE' => [
                'sheets' => [
                    'meta' => [
                        'langDisable' => 1,
                    ],
                    'sDEF' => [
                        'ROOT' => [],
                    ],
                ],
                true,
                false,
                [],
                [0 => 'DEF'],
            ],
            'langChildren is set to FALSE' => [
                'sheets' => [
                    'meta' => [
                        'langChildren' => 0,
                    ],
                    'sDEF' => [
                        'ROOT' => [],
                    ],
                ],
                false,
                false,
                [],
                [0 => 'DEF'],
            ],
            'langChildren is set to TRUE' => [
                'sheets' => [
                    'meta' => [
                        'langChildren' => 1,
                    ],
                    'sDEF' => [
                        'ROOT' => [],
                    ],
                ],
                false,
                true,
                [0 => 'DEF'],
                [],
            ],
            'langDisable and langChildren are set' => [
                'sheets' => [
                    'meta' => [
                        'langDisable' => 1,
                        'langChildren' => 1,
                    ],
                    'sDEF' => [
                        'ROOT' => [],
                    ],
                ],
                true,
                true,
                [0 => 'DEF'],
                [],
            ],
        ];
    }

    /**
     * @test
     * @param $flexform
     * @param $expectedLangDisable
     * @param $expectedLangChildren
     * @param $expectedLanguagesOnSheetLevel
     * @param $expectedLanguagesOnElement
     * @dataProvider addDataSetsLanguageFlagsDataProvider
     */
    public function addDataSetsLanguageFlags($flexform, $expectedLangDisable, $expectedLangChildren, $expectedLanguagesOnSheetLevel, $expectedLanguagesOnElement)
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'input_1' => [
                                    'vDEF' => 'input1 text',
                                ],
                            ],
                        ],
                    ],
                    'meta' => [],
                ],
                'pointerField' => 'aFlex',
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'iso' => 'DEF',
                ],
                1 => [
                    'uid' => 1,
                    'iso' => 'EN',
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'flex',
                            'ds_pointerField' => 'pointerField',
                            'ds' => $flexform,
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);

        $this->assertEquals($expectedLangDisable, $result['processedTca']['columns']['aField']['config']['ds']['meta']['langDisable']);
        $this->assertEquals($expectedLangChildren, $result['processedTca']['columns']['aField']['config']['ds']['meta']['langChildren']);
        $this->assertEquals($expectedLanguagesOnSheetLevel, $result['processedTca']['columns']['aField']['config']['ds']['meta']['languagesOnSheetLevel']);
        $this->assertEquals($expectedLanguagesOnElement, $result['processedTca']['columns']['aField']['config']['ds']['meta']['languagesOnElement']);
    }
}
