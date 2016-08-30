<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Export\PagesAndTtContentWithRteImagesAndFileLink;

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
    protected $coreExtensionsToLoad = [
        'rtehtmlarea',
        'impexp'
    ];

    /**
     * @var array
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/_processed_' => 'fileadmin/_processed_'
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/pages.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/tt_content-with-rte-image-n-file-link.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_reference.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_storage.xml');
    }

    /**
     * @test
     */
    public function exportPagesAndRelatedTtContentWithRteImagesAndFileLink()
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
                    'deleted',
                    'hidden',
                    'bodytext',
                    't3ver_oid'
                ],
                'sys_file' => [
                    'storage',
                    'type',
                    'metadata',
                    'identifier',
                    'identifier_hash',
                    'folder_hash',
                    'extension',
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
                ]
            ]
        );

        $this->export->relOnlyTables = [
            'sys_file',
            'sys_file_storage'
        ];

        $this->export->export_addRecord('pages', BackendUtility::getRecord('pages', 1));
        $this->export->export_addRecord('pages', BackendUtility::getRecord('pages', 2));
        $this->export->export_addRecord('tt_content', BackendUtility::getRecord('tt_content', 1));

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

        $out = $this->export->compileMemoryToFileContent('xml');

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/../../Fixtures/ImportExportXml/pages-and-ttcontent-with-rte-image-n-file-link.xml', $out);
    }
}
