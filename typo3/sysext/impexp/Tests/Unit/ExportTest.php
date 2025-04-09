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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Impexp\Export;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExportTest extends UnitTestCase
{
    public static function setExportFileNameSanitizesFileNameProvider(): array
    {
        return [
            [
                'fileName' => 'my-export-file_20201012 äöüß!"§$%&/()²³¼½¬{[]};,:µ<>|.1',
                'expected' => 'my-export-file_20201012.1',
            ],
        ];
    }

    #[DataProvider('setExportFileNameSanitizesFileNameProvider')]
    #[Test]
    public function setExportFileNameSanitizesFileName(string $fileName, string $expected): void
    {
        $exportMock = $this->getAccessibleMock(Export::class, null, [], '', false);
        $exportMock->setExportFileName($fileName);
        $actual = $exportMock->getExportFileName();
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function getOrGenerateExportFileNameWithFileExtensionConsidersPidAndLevels(): void
    {
        $exportMock = $this->getAccessibleMock(Export::class, null, [], '', false);
        $exportMock->setPid(1);
        $exportMock->setLevels(2);
        $patternDateTime = '[0-9-_]{16}';
        self::assertMatchesRegularExpression("/T3D_tree_PID1_L2_$patternDateTime.xml/", $exportMock->getOrGenerateExportFileNameWithFileExtension());
    }

    #[Test]
    public function getOrGenerateExportFileNameWithFileExtensionConsidersRecords(): void
    {
        $exportMock = $this->getAccessibleMock(Export::class, null, [], '', false);
        $exportMock->setRecord(['page:1', 'tt_content:1']);
        $patternDateTime = '[0-9-_]{16}';
        self::assertMatchesRegularExpression("/T3D_recs_page_1-tt_conte_$patternDateTime.xml/", $exportMock->getOrGenerateExportFileNameWithFileExtension());
    }

    #[Test]
    public function getOrGenerateExportFileNameWithFileExtensionConsidersLists(): void
    {
        $exportMock = $this->getAccessibleMock(Export::class, null, [], '', false);
        $exportMock->setList(['sys_news:0', 'news:12']);
        $patternDateTime = '[0-9-_]{16}';
        self::assertMatchesRegularExpression("/T3D_list_sys_news_0-news_$patternDateTime.xml/", $exportMock->getOrGenerateExportFileNameWithFileExtension());
    }

    public static function setExportFileTypeSucceedsWithSupportedFileTypeProvider(): array
    {
        return [
            ['fileType' => Export::FILETYPE_XML],
            ['fileType' => Export::FILETYPE_T3D],
            ['fileType' => Export::FILETYPE_T3DZ],
        ];
    }

    #[DataProvider('setExportFileTypeSucceedsWithSupportedFileTypeProvider')]
    #[Test]
    public function setExportFileTypeSucceedsWithSupportedFileType(string $fileType): void
    {
        $exportMock = $this->getAccessibleMock(Export::class, null, [], '', false);
        $exportMock->setExportFileType($fileType);
        self::assertEquals($fileType, $exportMock->getExportFileType());
    }

    #[Test]
    public function setExportFileTypeFailsWithUnsupportedFileType(): void
    {
        $this->expectException(\Exception::class);
        $exportMock = $this->getAccessibleMock(Export::class, null, [], '', false);
        $exportMock->setExportFileType('json');
    }

    public static function removeRedundantSoftRefsInRelationsProcessesOriginalRelationsArrayDataProvider(): array
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
                ],
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
                ],
            ],
        ];
    }

    #[DataProvider('removeRedundantSoftRefsInRelationsProcessesOriginalRelationsArrayDataProvider')]
    #[Test]
    public function removeRedundantSoftRefsInRelationsProcessesOriginalRelationsArray(array $relations, array $expected): void
    {
        $resourceFactoryMock = $this->createMock(ResourceFactory::class);
        $resourceFactoryMock->method('retrieveFileOrFolderObject')
            ->willReturnCallback(function (string $input): File {
                $fakeFileUidDerivedFromFileName = hexdec(substr(md5($input), 0, 6));
                $fileMock = $this->getAccessibleMock(
                    File::class,
                    null,
                    [],
                    '',
                    false
                );
                $fileMock->_set('properties', ['uid' => $fakeFileUidDerivedFromFileName]);
                return $fileMock;
            });
        $exportMock = $this->getAccessibleMock(Export::class, null, [], '', false);
        $exportMock->_set('resourceFactory', $resourceFactoryMock);
        self::assertEquals($expected, $exportMock->_call('removeRedundantSoftRefsInRelations', $relations));
    }

    public static function exportAddFilesFromRelationsSucceedsDataProvider(): array
    {
        $oneDat = [
            'files' => [
                'e580c5887dcea669332e96e25900b20b' => [],
            ],
            'records' => [
                'tt_content:8' => [
                    'data' => [],
                    'rels' => [
                        'pi_flexform' => [
                            'type' => 'flex',
                            'flexFormRels' => [
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $fullDat = [
            'files' => [
                'e580c5887dcea669332e96e25900b20b' => [],
            ],
            'records' => [
                'tt_content:8' => [
                    'data' => [],
                    'rels' => [
                        'pi_flexform' => [
                            'type' => 'flex',
                            'flexFormRels' => [
                                'softrefs' => [
                                    [
                                        'keys' => [
                                            [
                                                [
                                                    'subst' => [
                                                        'type' => 'file',
                                                        'tokenID' => 'tokenID',
                                                        'relFileName' => 'relFileNameSoftrefs',
                                                    ],
                                                ],
                                            ],
                                        ],
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
                                            ],
                                        ],
                                    ],
                                ],
                            ],
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
                    ],
                ],
            ],
        ];
        $fullExpected = $fullDat;
        $fullExpected['records']['tt_content:8']['rels']['pi_flexform']['flexFormRels']['softrefs'][0]['keys'][0][0]['file_ID'] = 'e580c5887dcea669332e96e25900b20b';

        return [
            'Empty $this->dat' => ['dat' => [], 'expected' => []],
            'Empty $this->dat[\'records\']' => ['dat' => ['records' => []], 'expected' => ['records' => []]],
            'One record example' => ['dat' => $oneDat, 'expected' => $oneDat],
            'Full example' => ['dat' => $fullDat, 'expected' => $fullExpected],
        ];
    }

    /**
     * Temporary test until there is a complex functional test which tests exportAddFilesFromRelations() implicitly.
     */
    #[DataProvider('exportAddFilesFromRelationsSucceedsDataProvider')]
    #[Test]
    public function exportAddFilesFromRelationsSucceeds(array $dat, array $expected): void
    {
        $exportMock = $this->getAccessibleMock(
            Export::class,
            ['addError', 'exportAddFile', 'isSoftRefIncluded'],
            [],
            '',
            false
        );
        $exportMock->method('isSoftRefIncluded')->willReturn(true);
        $exportMock->_set('dat', $dat);
        $exportMock->_call('exportAddFilesFromRelations');
        self::assertEquals($expected, $exportMock->_get('dat'));
    }
}
