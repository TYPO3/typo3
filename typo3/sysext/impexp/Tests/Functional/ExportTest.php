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

use PHPUnit\Framework\Attributes\RequiresFunction;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Impexp\Export;

final class ExportTest extends AbstractImportExportTestCase
{
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/template_extension',
    ];

    #[Test]
    public function creationAndDeletionOfTemporaryFolderSucceeds(): void
    {
        $subject = $this->get(Export::class);
        $temporaryFolderName = $subject->getOrCreateTemporaryFolderName();
        $temporaryFileName = $temporaryFolderName . '/export_file.txt';
        file_put_contents($temporaryFileName, 'Hello TYPO3 World.');
        self::assertDirectoryExists($temporaryFolderName);
        self::assertTrue(is_file($temporaryFileName));

        $subject->removeTemporaryFolderName();
        self::assertDirectoryDoesNotExist($temporaryFolderName);
        self::assertFalse(is_file($temporaryFileName));
    }

    #[Test]
    public function creationAndDeletionOfDefaultImportExportFolderSucceeds(): void
    {
        $subject = $this->get(Export::class);
        $exportFolder = $subject->getOrCreateDefaultImportExportFolder();
        $exportFileName = 'export_file.txt';
        $exportFolder->createFile($exportFileName);
        self::assertDirectoryExists(Environment::getPublicPath() . '/' . $exportFolder->getPublicUrl());
        self::assertTrue(is_file(Environment::getPublicPath() . '/' . $exportFolder->getPublicUrl() . $exportFileName));

        $subject->removeDefaultImportExportFolder();
        self::assertDirectoryDoesNotExist(Environment::getPublicPath() . '/' . $exportFolder->getPublicUrl());
        self::assertFalse(is_file(Environment::getPublicPath() . '/' . $exportFolder->getPublicUrl() . $exportFileName));
    }

    #[Test]
    public function renderPreviewWithoutArgumentsReturnsBasicArray(): void
    {
        $subject = $this->get(Export::class);
        $subject->process();
        $previewData = $subject->renderPreview();
        self::assertEquals([
            'update' => false,
            'showDiff' => false,
            'insidePageTree' => [],
            'outsidePageTree' => [],
        ], $previewData);
    }

    #[Test]
    public function renderPreviewForExportOfPageAndRecords(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file_storage.csv');

        $renderPreviewExport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportPageAndRecords.php';

        $subject = $this->get(Export::class);
        $subject->setPid(0);
        $subject->setLevels(Export::LEVELS_INFINITE);
        $subject->setTables(['_ALL']);
        $subject->process();
        $previewData = $subject->renderPreview();
        self::assertEquals($renderPreviewExport, $previewData);
    }

    #[Test]
    public function renderPreviewForExportOfPageAndRecordsWithSoftRefs(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content-with-softrefs.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file_storage.csv');

        $renderPreviewExport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportPageAndRecordsWithSoftRefs.php';

        $subject = $this->get(Export::class);
        $subject->setPid(0);
        $subject->setLevels(Export::LEVELS_INFINITE);
        $subject->setTables(['_ALL']);
        $subject->process();
        $previewData = $subject->renderPreview();
        self::assertEquals($renderPreviewExport, $previewData);
    }

    #[Test]
    public function renderPreviewForExportOfTable(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.csv');

        $renderPreviewExport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportTable.php';

        $subject = $this->get(Export::class);
        $subject->setList(['tt_content:1']);
        $subject->process();
        $previewData = $subject->renderPreview();
        self::assertEquals($renderPreviewExport, $previewData);
    }

    #[Test]
    public function renderPreviewForExportOfRecords(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.csv');

        $renderPreviewExport = include __DIR__ . '/Fixtures/ArrayAssertions/RenderPreviewExportRecords.php';

        $subject = $this->get(Export::class);
        $subject->setRecord(['tt_content:1', 'tt_content:2']);
        $subject->process();
        $previewData = $subject->renderPreview();
        self::assertEquals($renderPreviewExport, $previewData);
    }

    #[Test]
    public function renderSucceedsWithoutArguments(): void
    {
        $subject = $this->get(Export::class);
        $subject->process();
        $actual = $subject->render();
        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/Fixtures/XmlExports/empty.xml',
            $actual
        );
    }

    #[Test]
    public function exportWritesCurrentTypo3VersionIntoMetaBlock(): void
    {
        $subject = $this->get(Export::class);
        $subject->process();

        $xml = new \SimpleXMLElement($subject->render());
        self::assertSame(
            $this->get(Typo3Version::class)->getVersion(),
            (string)$xml->header->meta->TYPO3_version
        );
    }

    #[Test]
    public function saveXmlToFileIsDefaultAndSucceeds(): void
    {
        $subject = $this->get(Export::class);
        $subject->setExportFileName('export');
        $subject->process();
        $file = $subject->saveToFile();
        $filePath = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        $this->testFilesToDelete[] = $filePath;

        self::assertStringEndsWith('export.xml', $filePath);
        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/Fixtures/XmlExports/empty.xml',
            file_get_contents($filePath)
        );
    }

    #[Test]
    public function saveT3dToFileSucceeds(): void
    {
        $subject = $this->get(Export::class);
        $subject->setExportFileName('export');
        $subject->setExportFileType(Export::FILETYPE_T3D);
        $subject->process();
        $file = $subject->saveToFile();
        $filePath = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        $this->testFilesToDelete[] = $filePath;

        // remove final newlines
        $expected = trim(file_get_contents(__DIR__ . '/Fixtures/T3dExports/empty.t3d'));
        $actual = trim(file_get_contents($filePath));

        self::assertStringEndsWith('export.t3d', $filePath);
        self::assertEquals($expected, $actual);
    }

    #[Test]
    #[RequiresFunction('gzcompress')]
    public function saveT3dCompressedToFileSucceeds(): void
    {
        $subject = $this->get(Export::class);
        $subject->setExportFileName('export');
        $subject->setExportFileType(Export::FILETYPE_T3DZ);
        $subject->process();
        $file = $subject->saveToFile();
        $filePath = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        $this->testFilesToDelete[] = $filePath;

        // remove final newlines
        $expected = trim(file_get_contents(__DIR__ . '/Fixtures/T3dExports/empty-z.t3d'));
        $actual = trim(file_get_contents($filePath));

        self::assertStringEndsWith('export-z.t3d', $filePath);
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function saveToFileCleansUpTemporaryFolder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.csv');

        $fileDirectory = Environment::getVarPath() . '/transient';
        $numTemporaryFilesAndFoldersBeforeImport = iterator_count(new \FilesystemIterator($fileDirectory, \FilesystemIterator::SKIP_DOTS));

        $subject = $this->get(Export::class);
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->setSaveFilesOutsideExportFile(true);
        $subject->setExportFileName('export');
        $subject->process();
        $file = $subject->saveToFile();

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        self::assertCount($numTemporaryFilesAndFoldersBeforeImport, new \FilesystemIterator($fileDirectory, \FilesystemIterator::SKIP_DOTS));
        $temporaryFolderName = (\Closure::bind(
            static fn() => $subject->temporaryFolderName,
            null,
            Export::class
        ))();
        self::assertEmpty($temporaryFolderName);
    }

    #[Test]
    public function saveToFileCleansUpFormerExportsOfSameName(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.csv');

        $subject = $this->get(Export::class);
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->setSaveFilesOutsideExportFile(true);
        $subject->setExportFileName('export');
        $subject->process();
        $subject->saveToFile();

        /** @var Folder $importExportFolder */
        $importExportFolder = (\Closure::bind(
            static fn() => $subject->defaultImportExportFolder,
            null,
            Export::class
        ))();
        $filesFolderName = 'export.xml.files';
        self::assertTrue($importExportFolder->hasFolder($filesFolderName));

        $subject->setSaveFilesOutsideExportFile(false);
        $subject->process();
        $file = $subject->saveToFile();

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        self::assertFalse($importExportFolder->hasFolder($filesFolderName));
    }

    #[Test]
    public function filterRecordFieldsKeepsNonDefaultValuesRegardlessOfSubSchema(): void
    {
        $subject = $this->get(Export::class);

        $row = [
            'uid' => 1,
            'pid' => 1,
            'CType' => 'textpic',
            'header' => 'Test Header',
            'bodytext' => 'Some text',
            'image' => '1',
        ];

        $result = (\Closure::bind(
            static fn() => $subject->filterRecordFields('tt_content', $row),
            null,
            Export::class
        ))();

        // uid and pid are always kept via defaultRecordIncludeFields;
        // CType is always kept as the record-type field.
        self::assertArrayHasKey('uid', $result);
        self::assertArrayHasKey('pid', $result);
        self::assertArrayHasKey('CType', $result);
        // Non-default values survive the filter regardless of which sub-schema
        // the row belongs to.
        self::assertArrayHasKey('header', $result);
        self::assertArrayHasKey('bodytext', $result);
        self::assertArrayHasKey('image', $result);
    }

    #[Test]
    public function filterRecordFieldsDropsValuesMatchingTcaDefault(): void
    {
        $subject = $this->get(Export::class);

        // colPos carries an explicit TCA default of 0 — a stored 0 equals that
        // default and is stripped from the export.
        $row = [
            'uid' => 1,
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Test Header',
            'colPos' => 0,
        ];

        $result = (\Closure::bind(
            static fn() => $subject->filterRecordFields('tt_content', $row),
            null,
            Export::class
        ))();

        self::assertArrayHasKey('uid', $result);
        self::assertArrayHasKey('pid', $result);
        self::assertArrayHasKey('header', $result);
        // CType is preserved unconditionally as the record-type field, even
        // when the stored value equals the TCA default.
        self::assertArrayHasKey('CType', $result);
        self::assertArrayNotHasKey('colPos', $result);
    }

    #[Test]
    public function filterRecordFieldsKeepsImageFieldForTextpicCType(): void
    {
        $subject = $this->get(Export::class);

        $row = [
            'uid' => 1,
            'pid' => 1,
            'CType' => 'textpic',
            'header' => 'Test Header',
            'bodytext' => 'Some text',
            'image' => '1',
        ];

        $result = (\Closure::bind(
            static fn() => $subject->filterRecordFields('tt_content', $row),
            null,
            Export::class
        ))();

        self::assertArrayHasKey('uid', $result);
        self::assertArrayHasKey('pid', $result);
        self::assertArrayHasKey('CType', $result);
        self::assertArrayHasKey('header', $result);
        self::assertArrayHasKey('bodytext', $result);
        // "image" IS in the "textpic" sub-schema, so it must be kept
        self::assertArrayHasKey('image', $result);
    }

    #[Test]
    public function filterRecordFieldsKeepsUidAndPidForSysFile(): void
    {
        $subject = $this->get(Export::class);

        $row = [
            'uid' => 1,
            'pid' => 0,
            'storage' => 1,
            'type' => 2,
            'identifier' => '/user_upload/test.jpg',
            'name' => 'test.jpg',
            'sha1' => 'abc123',
        ];

        $result = (\Closure::bind(
            static fn() => $subject->filterRecordFields('tt_content', $row),
            null,
            Export::class
        ))();

        self::assertArrayHasKey('uid', $result);
        self::assertArrayHasKey('pid', $result);
        self::assertArrayHasKey('storage', $result);
        self::assertArrayHasKey('identifier', $result);
        self::assertArrayHasKey('name', $result);
    }

    #[Test]
    public function filterRecordFieldsReturnsUnfilteredRowForUnknownTable(): void
    {
        $subject = $this->get(Export::class);

        $row = ['uid' => 1, 'pid' => 1, 'title' => 'Test'];

        $result = (\Closure::bind(
            static fn() => $subject->filterRecordFields('tx_nonexistent_table', $row),
            null,
            Export::class
        ))();

        self::assertSame($row, $result);
    }

    #[Test]
    public function filterRecordFieldsAlwaysKeepsDisabledFieldEvenAtDefault(): void
    {
        $subject = $this->get(Export::class);

        // hidden=0 matches the TCA default for tt_content's RestrictionDisabledField,
        // but the filter preserves it unconditionally: impexp's preview reads
        // the value directly and must not misread "absent" as "visible".
        $row = [
            'uid' => 1,
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Test Header',
            'hidden' => 0,
            'colPos' => 0,
        ];

        $result = (\Closure::bind(
            static fn() => $subject->filterRecordFields('tt_content', $row),
            null,
            Export::class
        ))();

        self::assertArrayHasKey('uid', $result);
        self::assertArrayHasKey('pid', $result);
        self::assertArrayHasKey('hidden', $result, 'hidden must always be kept (RestrictionDisabledField)');
        self::assertArrayHasKey('header', $result, 'non-default values are kept');
        // Other fields at their explicit TCA default get stripped as noise.
        // colPos carries `'default' => 0`.
        self::assertArrayNotHasKey('colPos', $result);
    }
}
