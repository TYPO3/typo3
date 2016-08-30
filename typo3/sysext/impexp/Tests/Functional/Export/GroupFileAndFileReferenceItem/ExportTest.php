<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Export\GroupFileAndFileReferenceItem;

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
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/impexp_group_files'
    ];

    /**
     * @var array
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/uploads/tx_impexpgroupfiles' => 'uploads/tx_impexpgroupfiles'
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/pages.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_storage.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/tx_impexpgroupfiles_item.xml');
    }

    /**
     * @test
     */
    public function exportGroupFileAndFileReferenceItem()
    {
        $this->compileExportGroupFileAndFileReferenceItem();

        $out = $this->export->compileMemoryToFileContent('xml');

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../../Fixtures/ImportExportXml/impexp-group-file-and-file_reference-item.xml', $out);
    }

    /**
     * @test
     */
    public function exportGroupFileAndFileReferenceItemButImagesNotIncluded()
    {
        $this->export->setSaveFilesOutsideExportFile(true);

        $this->compileExportGroupFileAndFileReferenceItem();

        $out = $this->export->compileMemoryToFileContent('xml');

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../../Fixtures/ImportExportXml/impexp-group-file-and-file_reference-item-but-images-not-included.xml', $out);

        $temporaryFilesDirectory = $this->export->getTemporaryFilesPathForExport();
        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/uploads/tx_impexpgroupfiles/typo3_image4.jpg', $temporaryFilesDirectory . 'e1c5c4e1e34e19e2facb438752e06c3f');
        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image5.jpg', $temporaryFilesDirectory . 'c3511df85d21bc578faf71c6a19eeb3ff44af370');
    }

    protected function compileExportGroupFileAndFileReferenceItem()
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
                'sys_file' => [
                    'storage',
                    'type',
                    'metadata',
                    'extension',
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
                'tx_impexpgroupfiles_item' => [
                    'title',
                    'deleted',
                    'hidden',
                    'images',
                    'image_references'
                ],

            ]
        );

        $this->export->relOnlyTables = [
            'sys_file',
            'sys_file_storage'
        ];

        $this->export->export_addRecord('pages', BackendUtility::getRecord('pages', 1));
        $this->export->export_addRecord('tx_impexpgroupfiles_item', BackendUtility::getRecord('tx_impexpgroupfiles_item', 1));

        $this->setPageTree(1, 0);

        // After adding ALL records we set relations:
        for ($a = 0; $a < 10; $a++) {
            $addR = $this->export->export_addDBRelations($a);
            if (empty($addR)) {
                break;
            }
        }

        // hacky, but the timestamp will change on every clone, so set the file
        // modification timestamp to the asserted value
        $success = @touch(PATH_site . 'uploads/tx_impexpgroupfiles/typo3_image4.jpg', 1393866824);
        if (!$success) {
            $this->markTestSkipped('Could not set file modification timestamp for a fixture binary file. This is required for running the test successful.');
        }

        $this->export->export_addFilesFromRelations();
        $this->export->export_addFilesFromSysFilesRecords();
    }
}
