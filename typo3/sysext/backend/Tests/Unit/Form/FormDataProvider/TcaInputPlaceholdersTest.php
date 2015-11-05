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

use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaInputPlaceholderRecord;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case
 */
class TcaInputPlaceholdersTest extends UnitTestCase
{
    /**
     * @var TcaInputPlaceholders
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new TcaInputPlaceholders();
    }

    /**
     * @test
     */
    public function addDataRemovesEmptyPlaceholderOption()
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
                        ]
                    ]
                ],
            ],
        ];

        $expected = $input;
        unset($expected['processedTca']['columns']['aField']['config']['placeholder']);

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsUnmodifiedSimpleStringPlaceholder()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => 'aPlaceholder',
                        ]
                    ]
                ],
            ],
        ];

        $expected = $input;

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsValueFromDatabaseRowAsPlaceholder()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'anotherField' => 'anotherPlaceholder'
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|anotherField',
                        ]
                    ]
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = 'anotherPlaceholder';

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsValueFromSelectTypeRelation()
    {
        $input = [
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
                        ]
                    ],
                    'aRelationField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'aForeignTable'
                        ]
                    ]
                ],
            ],
        ];

        $aForeignTableInput = [
            'tableName' => 'aForeignTable',
            'databaseRow' => [
                'aForeignField' => 'aForeignValue',
            ],
            'processedTca' => [
                'columns' => [
                    'aForeignField' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                ],
            ],
        ];

        /** @var FormDataCompiler|ObjectProphecy $formDataCompilerProphecy */
        $formDataCompilerProphecy = $this->prophesize(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $formDataCompilerProphecy->reveal());
        $formDataCompilerProphecy->compile([
            'command' => 'edit',
            'vanillaUid' => 42,
            'tableName' => 'aForeignTable',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['aForeignField']
        ])
            ->shouldBeCalled()
            ->willReturn($aForeignTableInput);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $aForeignTableInput['databaseRow']['aForeignField'];

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsNoPlaceholderForNewSelectTypeRelation()
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
                        ]
                    ],
                    'aRelationField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'aForeignTable'
                        ]
                    ]
                ],
            ],
        ];

        $expected = $input;
        unset($expected['processedTca']['columns']['aField']['config']['placeholder']);

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsValueFromGroupTypeRelation()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
                'uid_local' => 'sys_file_3|aLabel,sys_file_5|anotherLabel',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|uid_local|sha1',
                        ]
                    ],
                    'uid_local' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'db',
                            'allowed' => 'sys_file'
                        ]
                    ]
                ],
            ],
        ];

        $sysFileProphecyResult = [
            'tableName' => 'sys_file',
            'databaseRow' => [
                'sha1' => 'aSha1Value',
            ],
            'processedTca' => [
                'columns' => [
                    'sha1' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                ],
            ],
        ];

        /** @var TcaInputPlaceholderRecord $languageService */
        $formDataCompilerProphecy = $this->prophesize(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $formDataCompilerProphecy->reveal());
        $formDataCompilerProphecy->compile([
            'command' => 'edit',
            'vanillaUid' => 3,
            'tableName' => 'sys_file',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['sha1']
        ])
            ->shouldBeCalled()
            ->willReturn($sysFileProphecyResult);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $sysFileProphecyResult['databaseRow']['sha1'];

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsValueFromInlineTypeRelation()
    {
        $input = [
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
                        ]
                    ],
                    'metadata' => [
                        'config' => [
                            'readOnly' => 1,
                            'type' => 'inline',
                            'foreign_table' => 'sys_file_metadata',
                            'foreign_field' => 'file',
                        ]
                    ]
                ],
            ],
        ];

        $sysFileMetadataProphecyResult = [
            'tableName' => 'sys_file_metadata',
            'databaseRow' => [
                'title' => 'aTitle',
            ],
            'processedTca' => [
                'columns' => [
                    'sha1' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                ],
            ],
        ];

        /** @var TcaInputPlaceholderRecord $languageService */
        $formDataCompilerProphecy = $this->prophesize(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $formDataCompilerProphecy->reveal());
        $formDataCompilerProphecy->compile([
            'command' => 'edit',
            'vanillaUid' => 2,
            'tableName' => 'sys_file_metadata',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['title']
        ])
            ->shouldBeCalled()
            ->willReturn($sysFileMetadataProphecyResult);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $sysFileMetadataProphecyResult['databaseRow']['title'];

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataReturnsValueFromRelationsRecursively()
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => '',
                'uid_local' => 'sys_file_3|aLabel,sys_file_5|anotherLabel',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                            'placeholder' => '__row|uid_local|metadata|title',
                        ]
                    ],
                    'uid_local' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'db',
                            'allowed' => 'sys_file'
                        ]
                    ]
                ],
            ],
        ];

        $sysFileProphecyResult = [
            'tableName' => 'sys_file',
            'databaseRow' => [
                'metadata' => '7',
            ],
            'processedTca' => [
                'columns' => [
                    'metadata' => [
                        'config' => [
                            'readOnly' => 1,
                            'type' => 'inline',
                            'foreign_table' => 'sys_file_metadata',
                            'foreign_field' => 'file',
                        ]
                    ]
                ],
            ],
        ];

        $sysFileMetadataProphecyResult = [
            'tableName' => 'sys_file_metadata',
            'databaseRow' => [
                'title' => 'aTitle',
            ],
            'processedTca' => [
                'columns' => [
                    'sha1' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                ],
            ],
        ];

        $sysFileFormDataCompilerProphecy = $this->prophesize(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $sysFileFormDataCompilerProphecy->reveal());
        $sysFileFormDataCompilerProphecy->compile([
            'command' => 'edit',
            'vanillaUid' => 3,
            'tableName' => 'sys_file',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['metadata']
        ])
            ->shouldBeCalled()
            ->willReturn($sysFileProphecyResult);

        $sysFileMetaDataFormDataCompilerProphecy = $this->prophesize(FormDataCompiler::class);
        GeneralUtility::addInstance(FormDataCompiler::class, $sysFileMetaDataFormDataCompilerProphecy->reveal());
        $sysFileMetaDataFormDataCompilerProphecy->compile([
            'command' => 'edit',
            'vanillaUid' => 7,
            'tableName' => 'sys_file_metadata',
            'inlineCompileExistingChildren' => false,
            'columnsToProcess' => ['title']
        ])
            ->shouldBeCalled()
            ->willReturn($sysFileMetadataProphecyResult);

        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $sysFileMetadataProphecyResult['databaseRow']['title'];

        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataCallsLanguageServiceForLocalizedPlaceholders()
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
                        ]
                    ]
                ],
            ],
        ];
        $expected = $input;
        $expected['processedTca']['columns']['aField']['config']['placeholder'] = $localizedString;

        /** @var LanguageService|ObjectProphecy $languageService */
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL($labelString)->shouldBeCalled()->willReturn($localizedString);

        $this->assertSame($expected, $this->subject->addData($input));
    }
}
