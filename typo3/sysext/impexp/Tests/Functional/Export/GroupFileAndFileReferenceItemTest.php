<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Export;

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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

/**
 * Test case
 */
class GroupFileAndFileReferenceItemTest extends AbstractImportExportTestCase
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

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_storage.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tx_impexpgroupfiles_item.xml');
    }

    /**
     * @test
     */
    public function exportGroupFileAndFileReferenceItem()
    {
        $subject = GeneralUtility::makeInstance(Export::class);
        $subject->init();

        $this->compileExportGroupFileAndFileReferenceItem($subject);

        $out = $subject->compileMemoryToFileContent('xml');

        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/impexp-group-file-and-file_reference-item.xml',
            $out
        );
    }

    /**
     * @test
     */
    public function exportGroupFileAndFileReferenceItemButImagesNotIncluded()
    {
        $subject = GeneralUtility::makeInstance(Export::class);
        $subject->init();

        $subject->setSaveFilesOutsideExportFile(true);

        $this->compileExportGroupFileAndFileReferenceItem($subject);

        $out = $subject->compileMemoryToFileContent('xml');

        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/impexp-group-file-and-file_reference-item-but-images-not-included.xml',
            $out
        );

        $temporaryFilesDirectory = $subject->getTemporaryFilesPathForExport();
        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/uploads/tx_impexpgroupfiles/typo3_image4.jpg', $temporaryFilesDirectory . 'e1c5c4e1e34e19e2facb438752e06c3f');
        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image5.jpg', $temporaryFilesDirectory . 'c3511df85d21bc578faf71c6a19eeb3ff44af370');
    }

    /**
     * Add default set of records to export
     *
     * @param $subject Export
     */
    protected function compileExportGroupFileAndFileReferenceItem(Export $subject)
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

        $subject->relOnlyTables = [
            'sys_file',
            'sys_file_storage'
        ];

        // @todo: Do not rely on BackendUtility::getRecord() in the test case itself
        $subject->export_addRecord('pages', $this->forceStringsOnRowValues(BackendUtility::getRecord('pages', 1)));
        $subject->export_addRecord('tx_impexpgroupfiles_item', $this->forceStringsOnRowValues(BackendUtility::getRecord('tx_impexpgroupfiles_item', 1)));

        $this->setPageTree($subject, 1, 0);

        // After adding ALL records we set relations:
        for ($a = 0; $a < 10; $a++) {
            $addR = $subject->export_addDBRelations($a);
            if (empty($addR)) {
                break;
            }
        }

        // Hacky, but the timestamp will change on every clone, so set the file modification timestamp to the asserted value
        $success = touch(Environment::getPublicPath() . '/uploads/tx_impexpgroupfiles/typo3_image4.jpg', 1393866824);
        // Early fail if touching failed - indicates a broken functional test setup
        $this->assertTrue($success, 'Could not set file modification timestamp for a fixture binary file.');

        $subject->export_addFilesFromRelations();
        $subject->export_addFilesFromSysFilesRecords();
    }
}
