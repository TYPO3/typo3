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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Impexp\Exception\LoadingFileFailedException;
use TYPO3\CMS\Impexp\Import;

final class ImportTest extends AbstractImportExportTestCase
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

    #[DataProvider('loadingFileFromWithinTypo3BaseFolderSucceedsProvider')]
    #[Test]
    #[DoesNotPerformAssertions]
    public function loadingFileFromWithinTypo3BaseFolderSucceeds(string $filePath): void
    {
        $filePath = str_replace('%EnvironmentPublicPath%', Environment::getPublicPath(), $filePath);
        $subject = $this->get(Import::class);
        $subject->loadFile($filePath);
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

    #[DataProvider('loadingFileFailsProvider')]
    #[Test]
    public function loadingFileFails(string $filePath): void
    {
        $this->expectException(LoadingFileFailedException::class);
        $subject = $this->get(Import::class);
        $subject->loadFile($filePath);
    }

    #[Test]
    public function renderPreviewForImportOfPageAndRecords(): void
    {
        $renderPreviewImport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecords.php';
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml');
        $previewData = $subject->renderPreview();
        //        file_put_contents(
        //            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecords.php',
        //            str_replace(
        //                ['array (', '),', ');'],
        //                ['[', '],', '];'],
        //                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
        //        );
        self::assertEquals($renderPreviewImport, $previewData);
    }

    #[Test]
    public function renderPreviewForImportOfPageAndRecordsByUpdate(): void
    {
        $renderPreviewImport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsByUpdate.php';
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml');
        $subject->importData();
        $subject->setUpdate(true);
        $previewData = $subject->renderPreview();
        //        file_put_contents(
        //            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsByUpdate.php',
        //            str_replace(
        //                ['array (', '),', ');'],
        //                ['[', '],', '];'],
        //                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
        //        );
        self::assertEquals($renderPreviewImport, $previewData);
    }

    #[Test]
    public function renderPreviewForImportOfPageAndRecordsWithDiffView(): void
    {
        $renderPreviewImport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsWithDiff.php';
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml');
        $subject->importData();
        $subject->setShowDiff(true);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent-with-two-images.xml');
        $previewData = $subject->renderPreview();
        //        file_put_contents(
        //            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsWithDiff.php',
        //            str_replace(
        //                ['array (', '),', ');'],
        //                ['[', '],', '];'],
        //                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
        //        );
        self::assertEquals($renderPreviewImport, $previewData);
    }

    #[Test]
    public function renderPreviewForImportOfPageAndRecordsByUpdateWithDiffView(): void
    {
        $renderPreviewImport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsByUpdateWithDiff.php';
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml');
        $subject->importData();
        $subject->setShowDiff(true);
        $subject->setUpdate(true);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent-with-two-images.xml');
        $previewData = $subject->renderPreview();
        //        file_put_contents(
        //            __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsByUpdateWithDiff.php',
        //            str_replace(
        //                ['array (', '),', ');'],
        //                ['[', '],', '];'],
        //                '<?php' . "\n\nreturn " . var_export($previewData, true) . ";\n")
        //        );
        self::assertEquals($renderPreviewImport, $previewData);
    }

    #[Test]
    public function renderPreviewForImportOfPageAndRecordsWithSoftRefs(): void
    {
        $renderPreviewImport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewImportPageAndRecordsWithSoftRefs.php';
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent-with-softrefs.xml');
        $previewData = $subject->renderPreview();
        self::assertEquals($renderPreviewImport, $previewData);
    }

    public static function addFilesSucceedsDataProvider(): array
    {
        $fileMtimeHard = filemtime(__DIR__ . '/../../Resources/Public/Icons/status-reference-hard.png');
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
                        'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="FILE" class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-hard" data-identifier="status-reference-hard" aria-hidden="true">
' . "\t" . '<span class="icon-markup">
<img src="/typo3/sysext/impexp/Resources/Public/Icons/status-reference-hard.png?' . $fileMtimeHard . '" width="16" height="16" alt="" />
' . "\t" . '</span>' . "\n\t\n" . '</span>',
                        'title' => 'filename.jpg',
                        'showDiffContent' => '',
                    ],
                ], ],
        ];
    }

    /**
     * Temporary test until there is a complex functional test which tests addFiles() implicitly.
     */
    #[DataProvider('addFilesSucceedsDataProvider')]
    #[Test]
    public function addFilesSucceeds(array $dat, array $relations, string $tokenID, array $expected): void
    {
        $subject = $this->get(Import::class);
        $datProperty = new \ReflectionProperty($subject, 'dat');
        $datProperty->setValue($subject, $dat);
        $lines = [];
        $subject->addFiles($relations, $lines, 0, $tokenID);
        self::assertEquals($expected, $lines);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function loadXmlSucceeds(): void
    {
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlExports/empty.xml');
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function loadT3dSucceeds(): void
    {
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/T3dExports/empty.t3d');
    }

    #[Test]
    public function loadT3dFails(): void
    {
        $this->expectException(LoadingFileFailedException::class);
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/T3dExports/empty-with-wrong-checksum.t3d');
    }

    #[Test]
    #[RequiresPhpExtension('zlib')]
    #[DoesNotPerformAssertions]
    public function loadT3dCompressedSucceeds(): void
    {
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/T3dExports/empty-z.t3d');
    }
}
