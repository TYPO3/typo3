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
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\TcaSchemaBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaInputPlaceholdersTest extends FunctionalTestCase
{
    #[Test]
    public function addDataRemovesEmptyPlaceholderOption(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '',
                        ],
                    ],
                ],
            ],
        ];
        $input['tcaSchemata'] = $this->get(TcaSchemaBuilder::class)->buildFromStructure([$input['tableName'] => $input['processedTca']]);

        $expected = $input;
        unset($expected['processedTca']['columns']['aField']['config']['placeholder']);
        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataReturnsUnmodifiedSimpleStringPlaceholder(): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->withAnyParameters()->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageService;
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => 'aPlaceholder',
                        ],
                    ],
                ],
            ],
        ];
        $input['tcaSchemata'] = $this->get(TcaSchemaBuilder::class)->buildFromStructure([$input['tableName'] => $input['processedTca']]);
        $expected = $input;
        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataReturnsValueFromDatabaseRowAsPlaceholder(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'anotherField' => 'anotherPlaceholder',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|anotherField',
                        ],
                    ],
                ],
            ],
        ];
        $input['tcaSchemata'] = $this->get(TcaSchemaBuilder::class)->buildFromStructure([$input['tableName'] => $input['processedTca']]);
        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = 'anotherPlaceholder';
        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataReturnsValueFromSelectTypeRelation(): void
    {
        $request = new ServerRequest();
        $fullTca = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|aRelationField|aForeignField',
                        ],
                    ],
                    'aRelationField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'aForeignTable',
                        ],
                    ],
                ],
            ],
            'aForeignTable' => [
                'columns' => [
                    'aForeignField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];
        $tcaSchemata = $this->get(TcaSchemaBuilder::class)->buildFromStructure($fullTca);

        $input = [
            'request' => $request,
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
                'aRelationField' => ['42'],
            ],
            'processedTca' => [
                'columns' => $fullTca['aTable']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $aForeignTableInput = [
            'request' => $request,
            'tableName' => 'aForeignTable',
            'databaseRow' => [
                'aForeignField' => 'aForeignValue',
            ],
            'processedTca' => [
                'columns' => $fullTca['aForeignTable']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $formDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $formDataCompilerMock);
        $formDataCompilerMock->expects($this->atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 42,
            'tableName' => 'aForeignTable',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['aForeignField'],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ])
            ->willReturn($aForeignTableInput);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $aForeignTableInput['databaseRow']['aForeignField'];

        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataReturnsNoPlaceholderForNewSelectTypeRelation(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
                'aRelationField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|aRelationField|aForeignField',
                        ],
                    ],
                    'aRelationField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'aForeignTable',
                        ],
                    ],
                ],
            ],
        ];
        $input['tcaSchemata'] = $this->get(TcaSchemaBuilder::class)->buildFromStructure([$input['tableName'] => $input['processedTca']]);
        $expected = $input;
        unset($expected['processedTca']['columns']['aField']['config']['placeholder']);
        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataReturnsValueFromGroupTypeRelation(): void
    {
        $request = new ServerRequest();
        $fullTca = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|uid_local|sha1',
                        ],
                    ],
                    'uid_local' => [
                        'config' => [
                            'type' => 'group',
                            'allowed' => 'sys_file',
                        ],
                    ],
                ],
            ],
            'sys_file' => [
                'columns' => [
                    'sha1' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];
        $tcaSchemata = $this->get(TcaSchemaBuilder::class)->buildFromStructure($fullTca);

        $input = [
            'request' => $request,
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
                'uid_local' => [
                    [
                        'uid' => 3,
                        'table' => 'sys_file',
                    ],
                    [
                        'uid' => 5,
                        'table' => 'sys_file',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => $fullTca['aTable']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $sysFileMockResult = [
            'request' => $request,
            'tableName' => 'sys_file',
            'databaseRow' => [
                'sha1' => 'aSha1Value',
            ],
            'processedTca' => [
                'columns' => $fullTca['sys_file']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $formDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $formDataCompilerMock);
        $formDataCompilerMock->expects($this->atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 3,
            'tableName' => 'sys_file',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['sha1'],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ])
            ->willReturn($sysFileMockResult);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $sysFileMockResult['databaseRow']['sha1'];

        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataReturnsValueFromInlineTypeRelation(): void
    {
        $request = new ServerRequest();
        $fullTca = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|metadata|title',
                        ],
                    ],
                    'metadata' => [
                        'config' => [
                            'readOnly' => true,
                            'type' => 'inline',
                            'foreign_table' => 'sys_file_metadata',
                            'foreign_field' => 'file',
                        ],
                    ],
                ],
            ],
            'sys_file_metadata' => [
                'columns' => [
                    'title' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];
        $tcaSchemata = $this->get(TcaSchemaBuilder::class)->buildFromStructure($fullTca);

        $input = [
            'request' => $request,
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
                'metadata' => '2',
            ],
            'processedTca' => [
                'columns' => $fullTca['aTable']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $sysFileMetadataMockResult = [
            'request' => $request,
            'tableName' => 'sys_file_metadata',
            'databaseRow' => [
                'title' => 'aTitle',
            ],
            'processedTca' => [
                'columns' => $fullTca['sys_file_metadata']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $formDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $formDataCompilerMock);
        $formDataCompilerMock->expects($this->atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 2,
            'tableName' => 'sys_file_metadata',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['title'],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ])
            ->willReturn($sysFileMetadataMockResult);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $sysFileMetadataMockResult['databaseRow']['title'];

        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataReturnsValueFromRelationsRecursively(): void
    {
        $request = new ServerRequest();
        $fullTca = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|uid_local|metadata|title',
                        ],
                    ],
                    'uid_local' => [
                        'config' => [
                            'type' => 'group',
                            'allowed' => 'sys_file',
                        ],
                    ],
                ],
            ],
            'sys_file' => [
                'columns' => [
                    'metadata' => [
                        'config' => [
                            'readOnly' => true,
                            'type' => 'inline',
                            'foreign_table' => 'sys_file_metadata',
                            'foreign_field' => 'file',
                        ],
                    ],
                ],
            ],
            'sys_file_metadata' => [
                'columns' => [
                    'title' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];
        $tcaSchemata = $this->get(TcaSchemaBuilder::class)->buildFromStructure($fullTca);

        $input = [
            'request' => $request,
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
                'uid_local' => [
                    [
                        'uid' => 3,
                        'table' => 'sys_file',
                    ],
                    [
                        'uid' => 5,
                        'table' => 'sys_file',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => $fullTca['aTable']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $sysFileMockResult = [
            'request' => $request,
            'tableName' => 'sys_file',
            'databaseRow' => [
                'metadata' => '7',
            ],
            'processedTca' => [
                'columns' => $fullTca['sys_file']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $sysFileMetadataMockResult = [
            'request' => $request,
            'tableName' => 'sys_file_metadata',
            'databaseRow' => [
                'title' => 'aTitle',
            ],
            'processedTca' => [
                'columns' => $fullTca['sys_file_metadata']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $sysFileFormDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $sysFileFormDataCompilerMock);
        $sysFileFormDataCompilerMock->expects($this->atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 3,
            'tableName' => 'sys_file',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['metadata'],
            'tcaSchemata' => $input['tcaSchemata'],
            'fullTca' => $input['fullTca'],
        ])
            ->willReturn($sysFileMockResult);

        $sysFileMetaDataFormDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $sysFileMetaDataFormDataCompilerMock);
        $sysFileMetaDataFormDataCompilerMock->expects($this->atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 7,
            'tableName' => 'sys_file_metadata',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['title'],
            'tcaSchemata' => $sysFileMockResult['tcaSchemata'],
            'fullTca' => $sysFileMockResult['fullTca'],
        ])
            ->willReturn($sysFileMetadataMockResult);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $sysFileMetadataMockResult['databaseRow']['title'];

        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataReturnsImplodedValueFromRelationAtDeeperRecursionLevel(): void
    {
        $request = new ServerRequest();
        $fullTca = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|aRelationField|aForeignField',
                        ],
                    ],
                    'aRelationField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'aForeignTable',
                        ],
                    ],
                ],
            ],
            'aForeignTable' => [
                'columns' => [
                    'aForeignField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'aTable',
                        ],
                    ],
                ],
            ],
        ];
        $tcaSchemata = $this->get(TcaSchemaBuilder::class)->buildFromStructure($fullTca);

        $input = [
            'request' => $request,
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
                'aRelationField' => ['42'],
            ],
            'processedTca' => [
                'columns' => $fullTca['aTable']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $aForeignTableInput = [
            'request' => $request,
            'tableName' => 'aForeignTable',
            'databaseRow' => [
                // FormDataProviders resolve a select field to an array of uids
                'aForeignField' => ['7', '8'],
            ],
            'processedTca' => [
                'columns' => $fullTca['aForeignTable']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $formDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $formDataCompilerMock);
        $formDataCompilerMock->expects($this->atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 42,
            'tableName' => 'aForeignTable',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['aForeignField'],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ])
            ->willReturn($aForeignTableInput);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = '7, 8';

        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataRemovesPlaceholderForEmptyRelationAtDeeperRecursionLevel(): void
    {
        $request = new ServerRequest();
        $fullTca = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|aRelationField|aForeignField',
                        ],
                    ],
                    'aRelationField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'aForeignTable',
                        ],
                    ],
                ],
            ],
            'aForeignTable' => [
                'columns' => [
                    'aForeignField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'aTable',
                        ],
                    ],
                ],
            ],
        ];
        $tcaSchemata = $this->get(TcaSchemaBuilder::class)->buildFromStructure($fullTca);

        $input = [
            'request' => $request,
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
                'aRelationField' => ['42'],
            ],
            'processedTca' => [
                'columns' => $fullTca['aTable']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $aForeignTableInput = [
            'request' => $request,
            'tableName' => 'aForeignTable',
            'databaseRow' => [
                // Nothing has been selected in the related record
                'aForeignField' => [],
            ],
            'processedTca' => [
                'columns' => $fullTca['aForeignTable']['columns'],
            ],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ];

        $formDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $formDataCompilerMock);
        $formDataCompilerMock->expects($this->atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 42,
            'tableName' => 'aForeignTable',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['aForeignField'],
            'tcaSchemata' => $tcaSchemata,
            'fullTca' => $fullTca,
        ])
            ->willReturn($aForeignTableInput);

        $expected = $input;
        unset($expected['processedTca']['columns']['aField']['config']['placeholder']);

        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataCallsLanguageServiceForLocalizedPlaceholders(): void
    {
        $labelString = 'LLL:EXT:some_ext/Resources/Private/Language/locallang.xlf:my_placeholder';
        $localizedString = 'My Placeholder';
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => $labelString,
                        ],
                    ],
                ],
            ],
        ];
        $input['tcaSchemata'] = $this->get(TcaSchemaBuilder::class)->buildFromStructure([$input['tableName'] => $input['processedTca']]);
        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $localizedString;

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->expects($this->atLeastOnce())->method('sL')->with($labelString)->willReturn($localizedString);

        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }
}
