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
        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = 'anotherPlaceholder';
        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataReturnsValueFromSelectTypeRelation(): void
    {
        $request = new ServerRequest();
        $input = [
            'request' => $request,
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
                'aRelationField' => ['42'],
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

        $aForeignTableInput = [
            'request' => $request,
            'tableName' => 'aForeignTable',
            'databaseRow' => [
                'aForeignField' => 'aForeignValue',
            ],
            'processedTca' => [
                'columns' => [
                    'aForeignField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $formDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $formDataCompilerMock);
        $formDataCompilerMock->expects(self::atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 42,
            'tableName' => 'aForeignTable',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['aForeignField'],
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
        $expected = $input;
        unset($expected['processedTca']['columns']['aField']['config']['placeholder']);
        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }

    #[Test]
    public function addDataReturnsValueFromGroupTypeRelation(): void
    {
        $request = new ServerRequest();
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
        ];

        $sysFileMockResult = [
            'request' => $request,
            'tableName' => 'sys_file',
            'databaseRow' => [
                'sha1' => 'aSha1Value',
            ],
            'processedTca' => [
                'columns' => [
                    'sha1' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $formDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $formDataCompilerMock);
        $formDataCompilerMock->expects(self::atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 3,
            'tableName' => 'sys_file',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['sha1'],
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
        $input = [
            'request' => $request,
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
                'metadata' => '2',
            ],
            'processedTca' => [
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
        ];

        $sysFileMetadataMockResult = [
            'request' => $request,
            'tableName' => 'sys_file_metadata',
            'databaseRow' => [
                'title' => 'aTitle',
            ],
            'processedTca' => [
                'columns' => [
                    'sha1' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $formDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $formDataCompilerMock);
        $formDataCompilerMock->expects(self::atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 2,
            'tableName' => 'sys_file_metadata',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['title'],
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
        ];

        $sysFileMockResult = [
            'request' => $request,
            'tableName' => 'sys_file',
            'databaseRow' => [
                'metadata' => '7',
            ],
            'processedTca' => [
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
        ];

        $sysFileMetadataMockResult = [
            'request' => $request,
            'tableName' => 'sys_file_metadata',
            'databaseRow' => [
                'title' => 'aTitle',
            ],
            'processedTca' => [
                'columns' => [
                    'sha1' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $sysFileFormDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $sysFileFormDataCompilerMock);
        $sysFileFormDataCompilerMock->expects(self::atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 3,
            'tableName' => 'sys_file',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['metadata'],
        ])
            ->willReturn($sysFileMockResult);

        $sysFileMetaDataFormDataCompilerMock = $this->createMock(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $sysFileMetaDataFormDataCompilerMock);
        $sysFileMetaDataFormDataCompilerMock->expects(self::atLeastOnce())->method('compile')->with([
            'request' => $request,
            'command' => 'edit',
            'vanillaUid' => 7,
            'tableName' => 'sys_file_metadata',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['title'],
        ])
            ->willReturn($sysFileMetadataMockResult);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $sysFileMetadataMockResult['databaseRow']['title'];

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
        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $localizedString;

        $languageService = $this->createMock(LanguageService::class);
        $GLOBALS['LANG'] = $languageService;
        $languageService->expects(self::atLeastOnce())->method('sL')->with($labelString)->willReturn($localizedString);

        self::assertSame($expected, $this->get(TcaInputPlaceholders::class)->addData($input));
    }
}
