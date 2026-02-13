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
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Resource\DefaultUploadFolderResolver;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Impexp\Export;

final class ExportTest extends AbstractImportExportTestCase
{
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/template_extension',
    ];

    private array $recordTypesIncludeFields =
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
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
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
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
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
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
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
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $subject->process();
        $previewData = $subject->renderPreview();
        self::assertEquals($renderPreviewExport, $previewData);
    }

    #[Test]
    public function renderSucceedsWithoutArguments(): void
    {
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [], '', false);
        $subject->process();
        $actual = $subject->render();
        self::assertXmlStringEqualsXmlFile(__DIR__ . '/Fixtures/XmlExports/empty.xml', $actual);
    }

    #[Test]
    public function saveXmlToFileIsDefaultAndSucceeds(): void
    {
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [], '', false);
        $subject->injectDefaultUploadFolderResolver($this->get(DefaultUploadFolderResolver::class));
        $subject->setExportFileName('export');
        $subject->process();
        $file = $subject->saveToFile();
        $filePath = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        $this->testFilesToDelete[] = $filePath;

        self::assertStringEndsWith('export.xml', $filePath);
        self::assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/XmlExports/empty.xml', $filePath);
    }

    #[Test]
    public function saveT3dToFileSucceeds(): void
    {
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [], '', false);
        $subject->injectDefaultUploadFolderResolver($this->get(DefaultUploadFolderResolver::class));
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
        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [], '', false);
        $subject->injectDefaultUploadFolderResolver($this->get(DefaultUploadFolderResolver::class));
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

        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
            $this->get(SiteConfiguration::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->injectResourceFactory($this->get(ResourceFactory::class));
        $subject->injectDefaultUploadFolderResolver($this->get(DefaultUploadFolderResolver::class));
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $subject->setSaveFilesOutsideExportFile(true);
        $subject->setExportFileName('export');
        $subject->process();
        $file = $subject->saveToFile();

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        self::assertCount($numTemporaryFilesAndFoldersBeforeImport, new \FilesystemIterator($fileDirectory, \FilesystemIterator::SKIP_DOTS));
        self::assertEmpty($subject->_get('temporaryFolderName'));
    }

    #[Test]
    public function saveToFileCleansUpFormerExportsOfSameName(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/sys_file-export-pages-and-tt-content.csv');

        $subject = $this->getAccessibleMock(Export::class, ['setMetaData'], [
            $this->get(ConnectionPool::class),
            $this->get(Locales::class),
            $this->get(Typo3Version::class),
            $this->get(ReferenceIndex::class),
            $this->get(SiteConfiguration::class),
        ]);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));
        $subject->injectResourceFactory($this->get(ResourceFactory::class));
        $subject->injectDefaultUploadFolderResolver($this->get(DefaultUploadFolderResolver::class));
        $subject->setPid(1);
        $subject->setLevels(1);
        $subject->setTables(['_ALL']);
        $subject->setRelOnlyTables(['sys_file']);
        $subject->setRecordTypesIncludeFields($this->recordTypesIncludeFields);
        $subject->setSaveFilesOutsideExportFile(true);
        $subject->setExportFileName('export');
        $subject->process();
        $subject->saveToFile();

        /** @var Folder $importExportFolder */
        $importExportFolder = $subject->_get('defaultImportExportFolder');
        $filesFolderName = 'export.xml.files';
        self::assertTrue($importExportFolder->hasFolder($filesFolderName));

        $subject->setSaveFilesOutsideExportFile(false);
        $subject->process();
        $file = $subject->saveToFile();

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/' . $file->getPublicUrl();

        self::assertFalse($importExportFolder->hasFolder($filesFolderName));
    }

    #[Test]
    public function filterRecordFieldsRemovesFieldsNotInSubSchemaForTextCType(): void
    {
        $subject = $this->getAccessibleMock(Export::class, null, [], '', false);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));

        $row = [
            'uid' => 1,
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Test Header',
            'bodytext' => 'Some text',
            'image' => '1',
        ];

        $result = $subject->_call('filterRecordFields', 'tt_content', $row);

        // uid and pid are always kept via defaultRecordIncludeFields
        self::assertArrayHasKey('uid', $result);
        self::assertArrayHasKey('pid', $result);
        // Type field is always kept
        self::assertArrayHasKey('CType', $result);
        // Fields in the "text" sub-schema are kept
        self::assertArrayHasKey('header', $result);
        self::assertArrayHasKey('bodytext', $result);
        // "image" is registered in the main tt_content schema but NOT in "text" sub-schema
        self::assertArrayNotHasKey('image', $result);
    }

    #[Test]
    public function filterRecordFieldsKeepsImageFieldForTextpicCType(): void
    {
        $subject = $this->getAccessibleMock(Export::class, null, [], '', false);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));

        $row = [
            'uid' => 1,
            'pid' => 1,
            'CType' => 'textpic',
            'header' => 'Test Header',
            'bodytext' => 'Some text',
            'image' => '1',
        ];

        $result = $subject->_call('filterRecordFields', 'tt_content', $row);

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
        $subject = $this->getAccessibleMock(Export::class, null, [], '', false);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));

        $row = [
            'uid' => 1,
            'pid' => 0,
            'storage' => 1,
            'type' => 2,
            'identifier' => '/user_upload/test.jpg',
            'name' => 'test.jpg',
            'sha1' => 'abc123',
        ];

        $result = $subject->_call('filterRecordFields', 'sys_file', $row);

        self::assertArrayHasKey('uid', $result);
        self::assertArrayHasKey('pid', $result);
        self::assertArrayHasKey('storage', $result);
        self::assertArrayHasKey('identifier', $result);
        self::assertArrayHasKey('name', $result);
    }

    #[Test]
    public function filterRecordFieldsReturnsUnfilteredRowForUnknownTable(): void
    {
        $subject = $this->getAccessibleMock(Export::class, null, [], '', false);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));

        $row = ['uid' => 1, 'pid' => 1, 'title' => 'Test'];

        $result = $subject->_call('filterRecordFields', 'tx_nonexistent_table', $row);
        self::assertSame($row, $result);
    }

    #[Test]
    public function filterRecordFieldsKeepsSystemFieldsForTextCType(): void
    {
        $subject = $this->getAccessibleMock(Export::class, null, [], '', false);
        $subject->injectTcaSchemaFactory($this->get(TcaSchemaFactory::class));

        $row = [
            'uid' => 1,
            'pid' => 1,
            'CType' => 'text',
            'header' => 'Test Header',
            'sys_language_uid' => 0,
            'l18n_parent' => 0,
            'l10n_source' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'fe_group' => '',
            'editlock' => 0,
        ];

        $result = $subject->_call('filterRecordFields', 'tt_content', $row);

        self::assertArrayHasKey('sys_language_uid', $result, 'sys_language_uid must not be filtered out');
        self::assertArrayHasKey('l18n_parent', $result, 'l18n_parent must not be filtered out');
        self::assertArrayHasKey('l10n_source', $result, 'l10n_source must not be filtered out');
        self::assertArrayHasKey('hidden', $result, 'hidden must not be filtered out');
        self::assertArrayHasKey('starttime', $result, 'starttime must not be filtered out');
        self::assertArrayHasKey('endtime', $result, 'endtime must not be filtered out');
        self::assertArrayHasKey('fe_group', $result, 'fe_group must not be filtered out');
        self::assertArrayHasKey('editlock', $result, 'editlock must not be filtered out');
    }
}
