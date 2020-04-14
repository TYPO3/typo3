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

namespace TYPO3\CMS\Impexp\Tests\Functional\Export;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

/**
 * Test case
 */
class PagesAndTtContentWithImagesTest extends AbstractImportExportTestCase
{
    /**
     * @var array
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tt_content-with-image.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_language.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_metadata.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_reference.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_storage.xml');
    }

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithImages()
    {
        $subject = GeneralUtility::makeInstance(Export::class);
        $subject->init();

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.xml');

        $this->compileExportPagesAndRelatedTtContentWithImages($subject);

        $out = $subject->compileMemoryToFileContent('xml');

        $errors = $subject->printErrorLog();
        self::assertSame('', $errors);

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-image.xml',
            $out
        );
    }

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithImagesFromCorruptSysFileRecord()
    {
        $subject = GeneralUtility::makeInstance(Export::class);
        $subject->init();

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_corrupt.xml');

        $this->compileExportPagesAndRelatedTtContentWithImages($subject);

        $out = $subject->compileMemoryToFileContent('xml');

        $expectedErrors = [
            'File sha1 hash of 1:/user_upload/typo3_image2.jpg is not up-to-date in index! File added on current sha1.'
        ];
        $errors = $subject->errorLog;
        self::assertSame($expectedErrors, $errors);

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-corrupt-image.xml',
            $out
        );
    }

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithImagesButNotIncluded()
    {
        $subject = GeneralUtility::makeInstance(Export::class);
        $subject->init();

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.xml');

        $subject->setSaveFilesOutsideExportFile(true);

        $this->compileExportPagesAndRelatedTtContentWithImages($subject);

        $out = $subject->compileMemoryToFileContent('xml');

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/pages-and-ttcontent-with-image-but-not-included.xml',
            $out
        );

        $temporaryFilesDirectory = $subject->getTemporaryFilesPathForExport();
        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', $temporaryFilesDirectory . 'da9acdf1e105784a57bbffec9520969578287797');
    }

    /**
     * Add default set of records to export
     *
     * @param $subject Export
     */
    protected function compileExportPagesAndRelatedTtContentWithImages(Export $subject)
    {
        $subject->setRecordTypesIncludeFields(
            [
                'pages' => [
                    'title',
                    'deleted',
                    'doktype',
                    'hidden',
                    'perms_everybody'
                ],
                'tt_content' => [
                    'CType',
                    'header',
                    'header_link',
                    'deleted',
                    'hidden',
                    'image',
                    't3ver_oid'
                ],
                'sys_language' => [
                    'uid',
                    'pid',
                    'hidden',
                    'title',
                    'flag'
                ],
                'sys_file_reference' => [
                    'uid_local',
                    'uid_foreign',
                    'tablenames',
                    'fieldname',
                    'sorting_foreign',
                    'table_local',
                    'title',
                    'description',
                    'alternative',
                    'link',
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
                'sys_file_storage' => [
                    'name',
                    'description',
                    'driver',
                    'configuration',
                    'is_default',
                    'is_browsable',
                    'is_public',
                    'is_writable',
                    'is_online'
                ],
                'sys_file_metadata' => [
                    'title',
                    'width',
                    'height',
                    'description',
                    'alternative',
                    'file',
                    'sys_language_uid',
                    'l10n_parent'
                ]
            ]
        );

        $subject->relOnlyTables = [
            'sys_file',
            'sys_file_metadata',
            'sys_file_storage',
            'sys_language'
        ];

        // @todo: Do not rely on BackendUtility::getRecord() in the test case itself
        $subject->export_addRecord('pages', $this->forceStringsOnRowValues(BackendUtility::getRecord('pages', 1)));
        $subject->export_addRecord('pages', $this->forceStringsOnRowValues(BackendUtility::getRecord('pages', 2)));
        $subject->export_addRecord('tt_content', $this->forceStringsOnRowValues(BackendUtility::getRecord('tt_content', 1)));
        $subject->export_addRecord('sys_language', $this->forceStringsOnRowValues(BackendUtility::getRecord('sys_language', 1)));
        $subject->export_addRecord('sys_file_reference', $this->forceStringsOnRowValues(BackendUtility::getRecord('sys_file_reference', 1)));

        $this->setPageTree($subject, 1, 1);

        // After adding ALL records we set relations:
        for ($a = 0; $a < 10; $a++) {
            $addR = $subject->export_addDBRelations($a);
            if (empty($addR)) {
                break;
            }
        }

        $subject->export_addFilesFromRelations();
        $subject->export_addFilesFromSysFilesRecords();
    }
}
