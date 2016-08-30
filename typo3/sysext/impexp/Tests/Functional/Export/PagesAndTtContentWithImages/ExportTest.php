<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Export\PagesAndTtContentWithImages;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Functional test for the Export
 */
class ExportTest extends \TYPO3\CMS\Impexp\Tests\Functional\Export\AbstractExportTestCase
{
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload'
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/pages.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/tt_content-with-image.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_language.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_metadata.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_reference.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_storage.xml');
    }

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithImages()
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file.xml');

        $this->compileExportPagesAndRelatedTtContentWithImages();

        $out = $this->export->compileMemoryToFileContent('xml');

        $errors = $this->export->printErrorLog();
        $this->assertSame('', $errors);

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../../Fixtures/ImportExportXml/pages-and-ttcontent-with-image.xml', $out);
    }

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithImagesFromCorruptSysFileRecord()
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_corrupt.xml');

        $this->compileExportPagesAndRelatedTtContentWithImages();

        $out = $this->export->compileMemoryToFileContent('xml');

        $expectedErrors = [
            'File size of 1:/user_upload/typo3_image2.jpg is not up-to-date in index! File added with current size.',
            'File sha1 hash of 1:/user_upload/typo3_image2.jpg is not up-to-date in index! File added on current sha1.'
        ];
        $errors = $this->export->errorLog;
        $this->assertSame($expectedErrors, $errors);

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../../Fixtures/ImportExportXml/pages-and-ttcontent-with-image.xml', $out);
    }

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithImagesButNotIncluded()
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file.xml');

        $this->export->setSaveFilesOutsideExportFile(true);

        $this->compileExportPagesAndRelatedTtContentWithImages();

        $out = $this->export->compileMemoryToFileContent('xml');

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../../Fixtures/ImportExportXml/pages-and-ttcontent-with-image-but-not-included.xml', $out);

        $temporaryFilesDirectory = $this->export->getTemporaryFilesPathForExport();
        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', $temporaryFilesDirectory . 'da9acdf1e105784a57bbffec9520969578287797');
    }

    protected function compileExportPagesAndRelatedTtContentWithImages()
    {
        $this->export->setRecordTypesIncludeFields(
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

        $this->export->relOnlyTables = [
            'sys_file',
            'sys_file_metadata',
            'sys_file_storage',
            'sys_language'
        ];

        $this->export->export_addRecord('pages', BackendUtility::getRecord('pages', 1));
        $this->export->export_addRecord('pages', BackendUtility::getRecord('pages', 2));
        $this->export->export_addRecord('tt_content', BackendUtility::getRecord('tt_content', 1));
        $this->export->export_addRecord('sys_language', BackendUtility::getRecord('sys_language', 1));
        $this->export->export_addRecord('sys_file_reference', BackendUtility::getRecord('sys_file_reference', 1));

        $this->setPageTree(1, 1);

        // After adding ALL records we set relations:
        for ($a = 0; $a < 10; $a++) {
            $addR = $this->export->export_addDBRelations($a);
            if (empty($addR)) {
                break;
            }
        }

        $this->export->export_addFilesFromRelations();
        $this->export->export_addFilesFromSysFilesRecords();
    }
}
