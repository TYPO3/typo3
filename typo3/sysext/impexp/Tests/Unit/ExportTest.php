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

namespace TYPO3\CMS\Impexp\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Export;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExportTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var Export|MockObject|AccessibleObjectInterface
     */
    protected $exportMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportMock = $this->getAccessibleMock(Export::class, ['dummy'], [], '', false);
        $this->exportMock->_set('compressionAvailable', true);
    }

    /**
     * @test
     * @dataProvider setExportFileNameSanitizesFileNameProvider
     * @param string $fileName
     * @param string $expected
     */
    public function setExportFileNameSanitizesFileName(string $fileName, string $expected): void
    {
        $this->exportMock->setExportFileName($fileName);
        $actual = $this->exportMock->getExportFileName();

        self::assertEquals($expected, $actual);
    }

    public function setExportFileNameSanitizesFileNameProvider(): array
    {
        return [
            [
                'fileName' => 'my-export-file_20201012 äöüß!"§$%&/()²³¼½¬{[]};,:µ<>|.1',
                'expected' => 'my-export-file_20201012.1'
            ],
        ];
    }

    /**
     * @test
     */
    public function getOrGenerateExportFileNameWithFileExtensionConsidersPidAndLevels(): void
    {
        $this->exportMock->setPid(1);
        $this->exportMock->setLevels(2);
        $patternDateTime = '[0-9-_]{16}';
        self::assertMatchesRegularExpression("/T3D_tree_PID1_L2_$patternDateTime.xml/", $this->exportMock->getOrGenerateExportFileNameWithFileExtension());
    }

    /**
     * @test
     */
    public function getOrGenerateExportFileNameWithFileExtensionConsidersRecords(): void
    {
        $this->exportMock->setRecord(['page:1', 'tt_content:1']);
        $patternDateTime = '[0-9-_]{16}';
        self::assertMatchesRegularExpression("/T3D_recs_page_1-tt_conte_$patternDateTime.xml/", $this->exportMock->getOrGenerateExportFileNameWithFileExtension());
    }

    /**
     * @test
     */
    public function getOrGenerateExportFileNameWithFileExtensionConsidersLists(): void
    {
        $this->exportMock->setList(['sys_language:0', 'news:12']);
        $patternDateTime = '[0-9-_]{16}';
        self::assertMatchesRegularExpression("/T3D_list_sys_language_0-_$patternDateTime.xml/", $this->exportMock->getOrGenerateExportFileNameWithFileExtension());
    }

    public function setExportFileTypeSucceedsWithSupportedFileTypeProvider(): array
    {
        return [
            ['fileType' => Export::FILETYPE_XML],
            ['fileType' => Export::FILETYPE_T3D],
            ['fileType' => Export::FILETYPE_T3DZ],
        ];
    }

    /**
     * @test
     * @dataProvider setExportFileTypeSucceedsWithSupportedFileTypeProvider
     * @param string $fileType
     */
    public function setExportFileTypeSucceedsWithSupportedFileType(string $fileType): void
    {
        $this->exportMock->setExportFileType($fileType);
        self::assertEquals($fileType, $this->exportMock->getExportFileType());
    }

    /**
     * @test
     */
    public function setExportFileTypeFailsWithUnsupportedFileType(): void
    {
        $this->expectException(\Exception::class);
        $this->exportMock->setExportFileType('json');
    }

    /**
     * @test
     */
    public function fixFileIdInRelationsProcessesOriginalRelationsArray(): void
    {
        $relations = [
            ['type' => 'file', 'newValueFiles' => [[
                'ID_absFile' => Environment::getPublicPath() . '/fileRelation.png'
            ]]],
            ['type' => 'flex', 'flexFormRels' => ['file' => [[[
                'ID_absFile' => Environment::getPublicPath() . '/fileRelationInFlexForm.png'
            ]]]]],
        ];

        $expected = [
            ['type' => 'file', 'newValueFiles' => [[
                'ID_absFile' => Environment::getPublicPath() . '/fileRelation.png',
                'ID' => '987eaa6ab0a50497101d373cfc983400',
            ]]],
            ['type' => 'flex', 'flexFormRels' => ['file' => [[[
                'ID_absFile' => Environment::getPublicPath() . '/fileRelationInFlexForm.png',
                'ID' => '4cd9d9637e042ebff3568ad4e0266e77',
            ]]]]],
        ];

        $this->exportMock->fixFileIdInRelations($relations);
        self::assertEquals($expected, $relations);
    }

    public function removeRedundantSoftRefsInRelationsProcessesOriginalRelationsArrayDataProvider(): array
    {
        return [
            'Remove one typolink entry from relation' => [
                'relations' => [
                    ['type' => 'db', 'itemArray' => [[
                        'id' => hexdec(substr(md5('fileRelation.png'), 0, 6)),
                        'table' => 'sys_file',
                    ]], 'softrefs' => ['keys' => ['typolink' => [
                        0 => ['subst' => ['type' => 'file', 'relFileName' => 'fileRelation.png']],
                        1 => ['subst' => ['type' => 'file', 'relFileName' => 'fileRelation2.png']],
                    ]]]],
                ],
                'expected' => [
                    ['type' => 'db', 'itemArray' => [[
                        'id' => hexdec(substr(md5('fileRelation.png'), 0, 6)),
                        'table' => 'sys_file',
                    ]], 'softrefs' => ['keys' => ['typolink' => [
                        1 => ['subst' => ['type' => 'file', 'relFileName' => 'fileRelation2.png']],
                    ]]]],
                ]
            ],
            'Remove whole softrefs array from relation' => [
                'relations' => [
                    ['type' => 'db', 'itemArray' => [[
                        'id' => hexdec(substr(md5('fileRelation2.png'), 0, 6)),
                        'table' => 'sys_file',
                    ]], 'softrefs' => ['keys' => ['typolink' => [
                        0 => ['subst' => ['type' => 'file', 'relFileName' => 'fileRelation2.png']],
                    ]]]],
                ],
                'expected' => [
                    ['type' => 'db', 'itemArray' => [[
                        'id' => hexdec(substr(md5('fileRelation2.png'), 0, 6)),
                        'table' => 'sys_file',
                    ]]],
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider removeRedundantSoftRefsInRelationsProcessesOriginalRelationsArrayDataProvider
     */
    public function removeRedundantSoftRefsInRelationsProcessesOriginalRelationsArray(array $relations, array $expected): void
    {
        $resourceFactoryMock = $this->getAccessibleMock(
            ResourceFactory::class,
            ['retrieveFileOrFolderObject'],
            [],
            '',
            false
        );
        $resourceFactoryMock->expects(self::any())->method('retrieveFileOrFolderObject')
            ->willReturnCallback(function ($relFileName) {
                $fakeFileUidDerivedFromFileName = hexdec(substr(md5($relFileName), 0, 6));
                $fileMock = $this->getAccessibleMock(
                    File::class,
                    ['dummy'],
                    [],
                    '',
                    false
                );
                $fileMock->_set('properties', ['uid' => $fakeFileUidDerivedFromFileName]);
                return $fileMock;
            });
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactoryMock);

        $this->exportMock->removeRedundantSoftRefsInRelations($relations);
        self::assertEquals($expected, $relations);
    }

    public function exportAddFilesFromRelationsSucceedsDataProvider(): array
    {
        $oneDat = [
            'files' => [
                'e580c5887dcea669332e96e25900b20b' => []
            ],
            'records' => [
                'tt_content:8' => [
                    'data' => [],
                    'rels' => [
                        'pi_flexform' => [
                            'type' => 'flex',
                            'flexFormRels' => [
                                'file' => [
                                    [
                                        [
                                            'filename' => 'filenameFlex',
                                            'ID_absFile' => 'ID_absFileFlex',
                                            'ID' => 'IDFlex',
                                            'relFileName' => 'relFileNameFlex',
                                        ],
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ];
        $oneExpected = $oneDat;
        unset($oneExpected['records']['tt_content:8']['rels']['pi_flexform']['flexFormRels']['file'][0][0]['ID_absFile']);

        $fullDat = [
            'files' => [
                'e580c5887dcea669332e96e25900b20b' => []
            ],
            'records' => [
                'tt_content:8' => [
                    'data' => [],
                    'rels' => [
                        'pi_flexform' => [
                            'type' => 'flex',
                            'flexFormRels' => [
                                'file' => [
                                    [
                                        [
                                            'filename' => 'filenameFlex',
                                            'ID_absFile' => 'ID_absFileFlex',
                                            'ID' => 'IDFlex',
                                            'relFileName' => 'relFileNameFlex',
                                        ],
                                    ]
                                ],
                                'softrefs' => [
                                    [
                                        'keys' => [
                                            [
                                                [
                                                    'subst' => [
                                                        'type' => 'file',
                                                        'tokenID' => 'tokenID',
                                                        'relFileName' => 'relFileNameSoftrefs',
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ],
                                ],
                            ],
                            'softrefs' => [
                                'keys' => [
                                    [
                                        [
                                            'subst' => [
                                                'type' => 'fileSoftrefs',
                                                'tokenID' => 'tokenIDSoftrefs',
                                                'relFileName' => 'relFileNameSoftrefsSoftrefs',
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'tx_bootstrappackage_carousel_item' => [
                            'type' => 'db',
                            'itemArray' => [
                                0 => [
                                    'id' => 2,
                                    'table' => 'tx_bootstrappackage_carousel_item',
                                ],
                            ],
                        ],
                        'background_image_options' => [
                            'type' => 'file',
                            'newValueFiles' => [
                                [
                                    'filename' => 'filenameFile',
                                    'ID_absFile' => 'ID_absFileFile',
                                    'ID' => 'IDFile',
                                    'relFileName' => 'relFileNameFile',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ];
        $fullExpected = $fullDat;
        $fullExpected['records']['tt_content:8']['rels']['pi_flexform']['flexFormRels']['softrefs'][0]['keys'][0][0]['file_ID'] = 'e580c5887dcea669332e96e25900b20b';
        unset($fullExpected['records']['tt_content:8']['rels']['pi_flexform']['flexFormRels']['file'][0][0]['ID_absFile']);
        unset($fullExpected['records']['tt_content:8']['rels']['background_image_options']['newValueFiles'][0]['ID_absFile']);

        return [
            'Empty $this->dat' => ['dat' => [], 'expected' => []],
            'Empty $this->dat[\'records\']' => ['dat' => ['records' => []], 'expected' => ['records' => []]],
            'One record example' => ['dat' => $oneDat, 'expected' => $oneExpected],
            'Full example' => ['dat' => $fullDat, 'expected' => $fullExpected],
        ];
    }

    /**
     * Temporary test until there is a complex functional test which tests exportAddFilesFromRelations() implicitly.
     *
     * @test
     * @dataProvider exportAddFilesFromRelationsSucceedsDataProvider
     */
    public function exportAddFilesFromRelationsSucceeds(array $dat, array $expected): void
    {
        $exportMock = $this->getAccessibleMock(
            Export::class,
            ['addError', 'exportAddFile', 'isSoftRefIncluded'],
            [],
            '',
            false
        );
        $exportMock->expects(self::any())->method('isSoftRefIncluded')->willReturn(true);

        $exportMock->_set('dat', $dat);
        $exportMock->_call('exportAddFilesFromRelations');
        self::assertEquals($expected, $exportMock->_get('dat'));
    }
}
