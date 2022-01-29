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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Form\FormDataGroup\FlexFormSegment;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultValues;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaFlexProcessTest extends FunctionalTestCase
{
    protected BackendUserAuthentication&MockObject $backendUserMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backendUserMock = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $this->backendUserMock;
        $GLOBALS['BE_USER']->groupData['non_exclude_fields'] = '';

        // Some tests call FormDataCompiler for sub elements. Those tests have functional test characteristics.
        // This is ok for the time being, but these settings takes care only parts of the compiler are called
        // to have fewer dependencies.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [];
    }

    #[Test]
    public function addDataThrowsExceptionWithMissingDataStructureIdentifier(): void
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

    #[Test]
    public function addDataRemovesSheetIfDisabledByPageTsConfig(): void
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

    #[Test]
    public function addDataSetsSheetTitleFromPageTsConfig(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

    #[Test]
    public function addDataSetsSheetDescriptionFromPageTsConfig(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

    #[Test]
    public function addDataSetsSheetShortDescriptionFromPageTsConfig(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

    #[Test]
    public function addDataSetsSheetShortDescriptionForSingleSheetFromPageTsConfig(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

    #[Test]
    public function addDataRemovesExcludeFieldFromDataStructure(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

        $this->backendUserMock->expects(self::atLeastOnce())->method('isAdmin')->willReturn(false);
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

    #[Test]
    public function addDataKeepsExcludeFieldInDataStructureWithUserAccess(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

        $this->backendUserMock->expects(self::atLeastOnce())->method('isAdmin')->willReturn(false);
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

    #[Test]
    public function addDataKeepsExcludeFieldInDataStructureForAdminUser(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

        $this->backendUserMock->expects(self::atLeastOnce())->method('isAdmin')->willReturn(true);
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

    #[Test]
    public function addDataRemovesPageTsDisabledFieldFromDataStructure(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

    #[Test]
    public function addDataHandlesPageTsConfigSettingsOfSingleFlexField(): void
    {
        $input = [
            'request' => new ServerRequest(),
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
                                                        'type' => 'group',
                                                        'allowed' => 'pages',
                                                        'size' => 3,
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
                                        'config.' => [
                                            'size' => 5,
                                        ],
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

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $this->backendUserMock->expects(self::atLeastOnce())->method('isAdmin')->willReturn(true);
        $this->backendUserMock->method('checkLanguageAccess')->with(self::anything())->willReturn(true);

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
                                    'type' => 'group',
                                    'allowed' => 'pages',
                                    'size' => 5,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    #[Test]
    public function addDataSetsDefaultValueFromFlexTcaForField(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $this->backendUserMock->expects(self::atLeastOnce())->method('isAdmin')->willReturn(true);
        $this->backendUserMock->method('checkLanguageAccess')->with(self::anything())->willReturn(true);

        $expected = $input;
        $expected['databaseRow']['aField']['data']['sDEF']['lDEF']['aFlexField']['vDEF'] = 'defaultValue';

        self::assertEquals($expected, (new TcaFlexProcess())->addData($input));
    }

    #[Test]
    public function addDataSetsValuesAndStructureForSectionContainerElements(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->method('sL')->with(self::anything())->willReturnArgument(0);

        $this->backendUserMock->method('isAdmin')->willReturn(true);
        $this->backendUserMock->method('checkLanguageAccess')->with(self::anything())->willReturn(true);

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

    #[Test]
    public function addDataCallsFlexFormSegmentGroupForFieldAndAddsFlexParentDatabaseRow(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

        $dummyGroup = $this->createMock(FlexFormSegment::class);
        GeneralUtility::addInstance(FlexFormSegment::class, $dummyGroup);

        // Check array given to flex group contains databaseRow as flexParentDatabaseRow and check compile is called
        $dummyGroup->expects(self::atLeastOnce())->method('compile')->with(self::callback(static function ($result) use ($input) {
            return $result['flexParentDatabaseRow'] === $input['databaseRow'];
        }))->willReturnArgument(0);

        (new TcaFlexProcess())->addData($input);
    }

    #[Test]
    public function addDataCallsFlexFormSegmentGroupForDummyContainerAndAddsFlexParentDatabaseRow(): void
    {
        $input = [
            'request' => new ServerRequest(),
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

        $dummyGroupExisting = $this->createMock(FlexFormSegment::class);
        GeneralUtility::addInstance(FlexFormSegment::class, $dummyGroupExisting);
        // Check array given to flex group contains databaseRow as flexParentDatabaseRow and check compile is called
        $dummyGroupExisting->expects(self::atLeastOnce())->method('compile')->with(self::callback(static function ($result) use ($input) {
            return $result['flexParentDatabaseRow'] === $input['databaseRow'];
        }))->willReturnArgument(0);

        (new TcaFlexProcess())->addData($input);
    }
}
