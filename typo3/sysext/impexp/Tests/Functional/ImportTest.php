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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Impexp\Exception\LoadingFileFailedException;
use TYPO3\CMS\Impexp\Import;

class ImportTest extends AbstractImportExportTestCase
{
    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/XmlImports' => 'fileadmin/xml_imports',
    ];

    public static function loadingFileFromWithinTypo3BaseFolderSucceedsProvider(): array
    {
        return [
            'relative path to fileadmin' => ['fileadmin/xml_imports/sys_news.xml'],
            'relative path to system extensions' => ['typo3/sysext/impexp/Tests/Functional/Fixtures/XmlImports/sys_news.xml'],
            'absolute path to system extensions' => ['%EnvironmentPublicPath%/typo3/sysext/impexp/Tests/Functional/Fixtures/XmlImports/sys_news.xml'],
            'extension path' => ['EXT:impexp/Tests/Functional/Fixtures/XmlImports/sys_news.xml'],
        ];
    }

    /**
     * @test
     * @dataProvider loadingFileFromWithinTypo3BaseFolderSucceedsProvider
     */
    public function loadingFileFromWithinTypo3BaseFolderSucceeds(string $filePath): void
    {
        $filePath = str_replace('%EnvironmentPublicPath%', Environment::getPublicPath(), $filePath);

        $importMock = $this->getAccessibleMock(Import::class, null);
        $importMock->loadFile($filePath);

        self::assertTrue(true);
    }

    public static function loadingFileFailsProvider(): array
    {
        return [
            'storage path' => ['1:/xml_imports/sys_news.xml'],
            'absolute path outside typo3 base folder' => ['/fileadmin/xml_imports/sys_news.xml'],
            'path to not existing file' => ['fileadmin/xml_imports/me_does_not_exist.xml'],
            'unsupported file extension' => ['EXT:impexp/Tests/Functional/Fixtures/XmlImports/unsupported.json'],
            'empty path' => [''],
        ];
    }

    /**
     * @test
     * @dataProvider loadingFileFailsProvider
     */
    public function loadingFileFails(string $filePath): void
    {
        $this->expectException(LoadingFileFailedException::class);

        $importMock = $this->getAccessibleMock(Import::class, ['loadInit']);
        $importMock->expects(self::never())->method('loadInit');
        $importMock->loadFile($filePath);
        self::assertEmpty($importMock->_get('dat'));
    }

    /**
     * @test
     */
    public function renderPreviewForImportOfPageAndRecords(): void
    {
        $renderPreviewImport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecords.php';

        $importMock = $this->getAccessibleMock(Import::class, null);
        $importMock->setPid(0);
        $importMock->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml');
        $previewData = $importMock->renderPreview();
//        file_put_contents(
//            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecords.php',
//            str_replace(
//                ['array (', '),', ');'],
//                ['[', '],', '];'],
//                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
//        );
        self::assertEquals($renderPreviewImport, $previewData);
    }

    /**
     * @test
     */
    public function renderPreviewForImportOfPageAndRecordsByUpdate(): void
    {
        $renderPreviewImport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsByUpdate.php';

        $importMock = $this->getAccessibleMock(Import::class, null);
        $importMock->setPid(0);
        $importMock->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml');
        $importMock->importData();
        $importMock->setUpdate(true);
        $previewData = $importMock->renderPreview();
//        file_put_contents(
//            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsByUpdate.php',
//            str_replace(
//                ['array (', '),', ');'],
//                ['[', '],', '];'],
//                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
//        );
        self::assertEquals($renderPreviewImport, $previewData);
    }

    /**
     * @test
     */
    public function renderPreviewForImportOfPageAndRecordsWithDiffView(): void
    {
        $renderPreviewImport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsWithDiff.php';

        $importMock = $this->getAccessibleMock(Import::class, null);
        $importMock->setPid(0);
        $importMock->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml');
        $importMock->importData();
        $importMock->setShowDiff(true);
        $importMock->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent-with-two-images.xml');
        $previewData = $importMock->renderPreview();
//        file_put_contents(
//            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsWithDiff.php',
//            str_replace(
//                ['array (', '),', ');'],
//                ['[', '],', '];'],
//                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
//        );
        self::assertEquals($renderPreviewImport, $previewData);
    }

    /**
     * @test
     */
    public function renderPreviewForImportOfPageAndRecordsByUpdateWithDiffView(): void
    {
        $renderPreviewImport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsByUpdateWithDiff.php';

        $importMock = $this->getAccessibleMock(Import::class, null);
        $importMock->setPid(0);
        $importMock->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml');
        $importMock->importData();
        $importMock->setShowDiff(true);
        $importMock->setUpdate(true);
        $importMock->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent-with-two-images.xml');
        $previewData = $importMock->renderPreview();
//        file_put_contents(
//            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsByUpdateWithDiff.php',
//            str_replace(
//                ['array (', '),', ');'],
//                ['[', '],', '];'],
//                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
//        );
        self::assertEquals($renderPreviewImport, $previewData);
    }

    /**
     * @test
     */
    public function renderPreviewForImportOfPageAndRecordsWithSoftRefs(): void
    {
        $renderPreviewImport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsWithSoftRefs.php';

        $importMock = $this->getAccessibleMock(Import::class, null);
        $importMock->setPid(0);
        $importMock->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent-with-softrefs.xml');
        $previewData = $importMock->renderPreview();
//        file_put_contents(
//            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsWithSoftRefs.php',
//            str_replace(
//                ['array (', '),', ');'],
//                ['[', '],', '];'],
//                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
//        );
        self::assertEquals($renderPreviewImport, $previewData);
    }

    public static function addFilesSucceedsDataProvider(): array
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
            ], 'tokenID' => '987654321'
                , 'expected' => [
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
            ], ],
        ];
    }

    /**
     * Temporary test until there is a complex functional test which tests addFiles() implicitly.
     *
     * @test
     * @dataProvider addFilesSucceedsDataProvider
     */
    public function addFilesSucceeds(array $dat, array $relations, string $tokenID, array $expected): void
    {
        $importMock = $this->getAccessibleMock(
            Import::class,
            ['addError'],
            [],
            '',
            true
        );

        $lines = [];
        $importMock->_set('dat', $dat);
        $importMock->addFiles($relations, $lines, 0, $tokenID);
        self::assertEquals($expected, $lines);
    }

    /**
     * @test
     */
    public function loadXmlSucceeds(): void
    {
        $importMock = $this->getAccessibleMock(Import::class, null);
        $importMock->setPid(0);
        $importMock->loadFile(
            'EXT:impexp/Tests/Functional/Fixtures/XmlExports/empty.xml',
            true
        );
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function loadT3dSucceeds(): void
    {
        $importMock = $this->getAccessibleMock(Import::class, null);
        $importMock->setPid(0);
        $importMock->loadFile(
            'EXT:impexp/Tests/Functional/Fixtures/T3dExports/empty.t3d',
            true
        );
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function loadT3dFails(): void
    {
        $this->expectException(LoadingFileFailedException::class);

        $importMock = $this->getAccessibleMock(Import::class, null);
        $importMock->setPid(0);
        $importMock->loadFile(
            'EXT:impexp/Tests/Functional/Fixtures/T3dExports/empty-with-wrong-checksum.t3d',
            true
        );
    }

    /**
     * @test
     */
    public function loadT3dCompressedSucceeds(): void
    {
        if (!function_exists('gzuncompress')) {
            self::markTestSkipped('The function gzuncompress() is not available for decompression.');
        }

        $importMock = $this->getAccessibleMock(Import::class, null);
        $importMock->setPid(0);
        $importMock->loadFile(
            'EXT:impexp/Tests/Functional/Fixtures/T3dExports/empty-z.t3d',
            true
        );
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function importDataCleansUpTemporaryFolder(): void
    {
        $fileDirectory = Environment::getVarPath() . '/transient';
        $numTemporaryFilesAndFoldersBeforeImport = iterator_count(new \FilesystemIterator($fileDirectory, \FilesystemIterator::SKIP_DOTS));

        $importMock = $this->getAccessibleMock(Import::class, null);
        $importMock->setPid(0);
        // Simulation of import of TCA field type=group with internal_type=file
        // which is not supported anymore since TYPO3 v10 but there are still remains in EXT:impexp.
        // Remove as soon as support of internal_type "file" has been completely removed from EXT:impexp.
        $dat = [
            'files' => [
                '123456789' => [
                    'content' => 'dummy content',
                    'content_md5' => md5('dummy content'),
                ],
            ],
        ];
        $fileInfo = [
            'ID' => '123456789',
        ];
        $files = [$fileInfo];
        $importMock->_set('dat', $dat);
        $importMock->writeFilesToTemporaryFolder($files);
        // End of simulation
        $importMock->loadFile(
            'EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml',
            true
        );
        $importMock->importData();

        self::assertCount($numTemporaryFilesAndFoldersBeforeImport, new \FilesystemIterator($fileDirectory, \FilesystemIterator::SKIP_DOTS));
        self::assertEmpty($importMock->_get('temporaryFolderName'));
    }
}
