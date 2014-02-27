<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Export\GroupFileAndFileReferenceItem;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Marc Bastian Heinrichs <typo3@mbh-software.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Utility\BackendUtility;

require_once __DIR__ . '/../AbstractExportTestCase.php';

/**
 * Functional test for the ImportExport
 */
class ExportTest extends \TYPO3\CMS\Impexp\Tests\Functional\Export\AbstractExportTestCase {

	/**
	 * @var array
	 */
	protected $testExtensionsToLoad = array(
		'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/impexp_group_files'
	);

	/**
	 * @var array
	 */
	protected $pathsToLinkInTestInstance = array(
		'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
		'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/uploads/tx_impexpgroupfiles' => 'uploads/tx_impexpgroupfiles'
	);

	public function setUp() {
		parent::setUp();

		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/pages.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_storage.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/tx_impexpgroupfiles_item.xml');

	}

	/**
	 * @test
	 */
	public function exportGroupFileAndFileReferenceItem() {

		$this->export->setRecordTypesIncludeFields(
			array(
				'pages' => array(
					'title',
					'deleted',
					'doktype',
					'hidden',
					'perms_everybody'
				),
				'sys_file' => array(
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
				),
				'sys_file_storage' => array(
					'name',
					'description',
					'driver',
					'configuration',
					'is_default',
					'is_browsable',
					'is_public',
					'is_writable',
					'is_online'
				),
				'tx_impexpgroupfiles_item' => array(
					'title',
					'deleted',
					'hidden',
					'images',
					'image_references'
				),

			)
		);

		$this->export->relOnlyTables = array(
			'sys_file',
			'sys_file_storage'
		);

		$this->export->export_addRecord('pages', BackendUtility::getRecord('pages', 1));
		$this->export->export_addRecord('tx_impexpgroupfiles_item', BackendUtility::getRecord('tx_impexpgroupfiles_item', 1));

		$this->setPageTree(1, 0);

		// After adding ALL records we set relations:
		for ($a = 0; $a < 10; $a++) {
			$addR = $this->export->export_addDBRelations($a);
			if (!count($addR)) {
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

		$out = $this->export->compileMemoryToFileContent('xml');

		$this->assertXmlStringEqualsXmlFile(__DIR__ . '/../../Fixtures/ImportExportXml/impexp-group-file-and-file_reference-item.xml', $out);
	}

}