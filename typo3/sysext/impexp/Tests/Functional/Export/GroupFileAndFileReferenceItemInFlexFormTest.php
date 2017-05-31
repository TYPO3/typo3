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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

/**
 * Test case
 */
class GroupFileAndFileReferenceItemInFlexFormTest extends AbstractImportExportTestCase
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
                    'image_references',
                    'flexform'
                ],

            ]
        );

        $subject->relOnlyTables = [
            'sys_file',
            'sys_file_storage'
        ];

        $subject->export_addRecord('pages', BackendUtility::getRecord('pages', 2));
        $subject->export_addRecord('tx_impexpgroupfiles_item', BackendUtility::getRecord('tx_impexpgroupfiles_item', 2));

        $this->setPageTree($subject, 2, 0);

        // After adding ALL records we set relations:
        for ($a = 0; $a < 10; $a++) {
            $addR = $subject->export_addDBRelations($a);
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

        $subject->export_addFilesFromRelations();
        $subject->export_addFilesFromSysFilesRecords();

        $out = $subject->compileMemoryToFileContent('xml');

        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/' . $this->databasePlatform . '/impexp-group-file-and-file_reference-item-in-ff.xml',
            $out
        );
    }
}
