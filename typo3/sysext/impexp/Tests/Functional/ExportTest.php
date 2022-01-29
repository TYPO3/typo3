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

namespace TYPO3\CMS\Impexp\Tests\Functional;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Impexp\Export;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

class ExportTest extends AbstractImportExportTestCase
{
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/template_extension',
    ];

    /**
     * @var Export|MockObject|AccessibleObjectInterface
     */
    protected $exportMock;

    /**
     * @var array
     */
    protected array $recordTypesIncludeFields =
        [
            'pages' => [
                'title',
                'deleted',
                'doktype',
                'hidden',
                'perms_everybody',
            ],
            'tt_content' => [
                'CType',
                'header',
                'header_link',
                'deleted',
                'hidden',
                't3ver_oid',
            ],
            'sys_file' => [
                'storage',
                'type',
                'metadata',
                'identifier',
                'identifier_hash',
                'folder_hash',
                'mime_type',
                'name',
                'sha1',
                'size',
                'creation_date',
                'modification_date',
            ],
        ]
    ;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportMock = $this->getAccessibleMock(Export::class, ['setMetaData']);
    }

    /**
     * @test
     */
    public function creationAndDeletionOfTemporaryFolderSucceeds(): void
    {
        $temporaryFolderName = $this->exportMock->getOrCreateTemporaryFolderName();
        $temporaryFileName = $temporaryFolderName . '/export_file.txt';
        file_put_contents($temporaryFileName, 'Hello TYPO3 World.');
        self::assertDirectoryExists($temporaryFolderName);
        self::assertTrue(is_file($temporaryFileName));

        $this->exportMock->removeTemporaryFolderName();
        self::assertDirectoryDoesNotExist($temporaryFolderName);
        self::assertFalse(is_file($temporaryFileName));
    }

    /**
     * @test
     */
    public function creationAndDeletionOfDefaultImportExportFolderSucceeds(): void
    {
        $exportFolder = $this->exportMock->getOrCreateDefaultImportExportFolder();
        $exportFileName = 'export_file.txt';
        $exportFolder->createFile($exportFileName);
        self::assertDirectoryExists(Environment::getPublicPath() . '/' . $exportFolder->getPublicUrl());
        self::assertTrue(is_file(Environment::getPublicPath() . '/' . $exportFolder->getPublicUrl() . $exportFileName));

        $this->exportMock->removeDefaultImportExportFolder();
        self::assertDirectoryDoesNotExist(Environment::getPublicPath() . '/' . $exportFolder->getPublicUrl());
        self::assertFalse(is_file(Environment::getPublicPath() . '/' . $exportFolder->getPublicUrl() . $exportFileName));
    }

    /**
     * @test
     */
    public function renderPreviewWithoutArgumentsReturnsBasicArray(): void
    {
        $this->exportMock->process();
        $previewData = $this->exportMock->renderPreview();
        self::assertEquals([
            'update' => false,
            'showDiff' => false,
            'insidePageTree' => [],
            'outsidePageTree' => [],
        ], $previewData);
    }

    /**
     * @test
     */
    public function renderPreviewForExportOfPageAndRecords(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.xml');

        $renderPreviewExport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportPageAndRecords.php';

        $this->exportMock->setPid(0);
        $this->exportMock->setLevels(Export::LEVELS_INFINITE);
        $this->exportMock->setTables(['_ALL']);
        $this->exportMock->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $this->exportMock->process();
        $previewData = $this->exportMock->renderPreview();
//        file_put_contents(
//            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportPageAndRecords.php',
//            str_replace(
//                ['array (', '),', ');'],
//                ['[', '],', '];'],
//                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
//        );
        self::assertEquals($renderPreviewExport, $previewData);
    }

    /**
     * @test
     */
    public function renderPreviewForExportOfPageAndRecordsWithSoftRefs(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content-with-softrefs.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.xml');

        $renderPreviewExport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportPageAndRecordsWithSoftRefs.php';

        $this->exportMock->setPid(0);
        $this->exportMock->setLevels(Export::LEVELS_INFINITE);
        $this->exportMock->setTables(['_ALL']);
        $this->exportMock->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $this->exportMock->process();
        $previewData = $this->exportMock->renderPreview();
//        file_put_contents(
//            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportPageAndRecordsWithSoftRefs.php',
//            str_replace(
//                ['array (', '),', ');'],
//                ['[', '],', '];'],
//                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
//        );
        self::assertEquals($renderPreviewExport, $previewData);
    }

    /**
     * @test
     */
    public function renderPreviewForExportOfTable(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.xml');

        $renderPreviewExport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportTable.php';

        $this->exportMock->setList(['tt_content:1']);
        $this->exportMock->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $this->exportMock->process();
        $previewData = $this->exportMock->renderPreview();
//        file_put_contents(
//            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportTable.php',
//            str_replace(
//                ['array (', '),', ');'],
//                ['[', '],', '];'],
//                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
//        );
        self::assertEquals($renderPreviewExport, $previewData);
    }

    /**
     * @test
     */
    public function renderPreviewForExportOfRecords(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.xml');

        $renderPreviewExport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportRecords.php';

        $this->exportMock->setRecord(['tt_content:1', 'tt_content:2']);
        $this->exportMock->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $this->exportMock->process();
        $previewData = $this->exportMock->renderPreview();
//        file_put_contents(
//            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportRecords.php',
//            str_replace(
//                ['array (', '),', ');'],
//                ['[', '],', '];'],
//                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
//        );
        self::assertEquals($renderPreviewExport, $previewData);
    }

    public function addFilesSucceedsDataProvider(): array
    {
        return [
            ['dat' => [
                'header' => [
                    'files' => [
                        '123456789' => [
                            'filename' => 'filename.jpg',
                            'relFileName' => 'filename.jpg',
                        ],
                    ],
                ],
            ], 'relations' => [
                '123456789',
            ], 'expected' => [
                [
                    'ref' => 'FILE',
                    'type' => 'file',
                    'msg' => '',
                    'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="FILE"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-hard" data-identifier="status-reference-hard">
' . "\t" . '<span class="icon-markup">
<img src="typo3/sysext/impexp/Resources/Public/Icons/status-reference-hard.png" width="16" height="16" alt="" />
' . "\t" . '</span>' . "\n\t\n" . '</span></span>',
                    'title' => 'filename.jpg',
                    'showDiffContent' => '',
                ],
            ]],
        ];
    }

    /**
     * Temporary test until there is a complex functional test which tests addFiles() implicitly.
     *
     * @test
     * @dataProvider addFilesSucceedsDataProvider
     */
    public function addFilesSucceeds(array $dat, array $relations, array $expected): void
    {
        $exportMock = $this->getAccessibleMock(
            Export::class,
            ['addError'],
            [],
            '',
            true
        );

        $lines = [];
        $exportMock->_set('dat', $dat);
        $exportMock->addFiles($relations, $lines, 0);
        self::assertEquals($expected, $lines);
    }

    /**
     * @test
     */
    public function renderSucceedsWithoutArguments(): void
    {
        $this->exportMock->process();
        $actual = $this->exportMock->render();

        self::assertXmlStringEqualsXmlFile(__DIR__ . '/Fixtures/XmlExports/empty.xml', $actual);
    }

    /**
     * @test
     */
    public function saveXmlToFileIsDefaultAndSucceeds(): void
    {
        $this->exportMock->setExportFileName('export');
        $this->exportMock->process();
        $file = $this->exportMock->saveToFile();
        $filePath = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        $this->testFilesToDelete[] = $filePath;

        self::assertStringEndsWith('export.xml', $filePath);
        self::assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/XmlExports/empty.xml', $filePath);
    }

    /**
     * @test
     */
    public function saveT3dToFileSucceeds(): void
    {
        $this->exportMock->setExportFileName('export');
        $this->exportMock->setExportFileType(Export::FILETYPE_T3D);
        $this->exportMock->process();
        $file = $this->exportMock->saveToFile();
        $filePath = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        $this->testFilesToDelete[] = $filePath;

        // remove final newlines
        $expected = trim(file_get_contents(__DIR__ . '/Fixtures/T3dExports/empty.t3d'));
        $actual = trim(file_get_contents($filePath));

        self::assertStringEndsWith('export.t3d', $filePath);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function saveT3dCompressedToFileSucceeds(): void
    {
        if (!function_exists('gzcompress')) {
            self::markTestSkipped('The function gzcompress() is not available for compression.');
        }

        $this->exportMock->setExportFileName('export');
        $this->exportMock->setExportFileType(Export::FILETYPE_T3DZ);
        $this->exportMock->process();
        $file = $this->exportMock->saveToFile();
        $filePath = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        $this->testFilesToDelete[] = $filePath;

        // remove final newlines
        $expected = trim(file_get_contents(__DIR__ . '/Fixtures/T3dExports/empty-z.t3d'));
        $actual = trim(file_get_contents($filePath));

        self::assertStringEndsWith('export-z.t3d', $filePath);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function saveToFileCleansUpTemporaryFolder(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.xml');

        $fileDirectory = Environment::getVarPath() . '/transient';
        $numTemporaryFilesAndFoldersBeforeImport = iterator_count(new \FilesystemIterator($fileDirectory, \FilesystemIterator::SKIP_DOTS));

        $this->exportMock->setPid(1);
        $this->exportMock->setLevels(1);
        $this->exportMock->setTables(['_ALL']);
        $this->exportMock->setRelOnlyTables(['sys_file']);
        $this->exportMock->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $this->exportMock->setSaveFilesOutsideExportFile(true);
        $this->exportMock->setExportFileName('export');
        $this->exportMock->process();
        $file = $this->exportMock->saveToFile();

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        self::assertCount($numTemporaryFilesAndFoldersBeforeImport, new \FilesystemIterator($fileDirectory, \FilesystemIterator::SKIP_DOTS));
        self::assertEmpty($this->exportMock->_get('temporaryFolderName'));
    }

    /**
     * @test
     */
    public function saveToFileCleansUpFormerExportsOfSameName(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.xml');

        $this->exportMock->setPid(1);
        $this->exportMock->setLevels(1);
        $this->exportMock->setTables(['_ALL']);
        $this->exportMock->setRelOnlyTables(['sys_file']);
        $this->exportMock->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $this->exportMock->setSaveFilesOutsideExportFile(true);
        $this->exportMock->setExportFileName('export');
        $this->exportMock->process();
        $this->exportMock->saveToFile();

        /** @var Folder $importExportFolder */
        $importExportFolder = $this->exportMock->_get('defaultImportExportFolder');
        $filesFolderName = 'export.xml.files';
        self::assertTrue($importExportFolder->hasFolder($filesFolderName));

        $this->exportMock->setSaveFilesOutsideExportFile(false);
        $this->exportMock->process();
        $file = $this->exportMock->saveToFile();

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        self::assertFalse($importExportFolder->hasFolder($filesFolderName));
    }
}
