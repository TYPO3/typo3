<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Export\PagesAndTtContentWithRteImagesAndFileLink;

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
	protected $coreExtensionsToLoad = array(
		'rtehtmlarea',
		'impexp'
	);

	/**
	 * @var array
	 */
	protected $pathsToLinkInTestInstance = array(
		'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
		'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/_processed_' => 'fileadmin/_processed_'
	);

	public function setUp() {
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
	public function exportPagesAndRelatedTtContentWithRteImagesAndFileLink() {

		$this->export->setRecordTypesIncludeFields(
			array(
				'pages' => array(
					'title',
					'deleted',
					'doktype',
					'hidden',
					'perms_everybody'
				),
				'tt_content' => array(
					'CType',
					'header',
					'deleted',
					'hidden',
					'bodytext',
					't3ver_oid'
				),
				'sys_file' => array(
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
				)
			)
		);

		$this->export->relOnlyTables = array(
			'sys_file',
			'sys_file_storage'
		);

		$this->export->export_addRecord('pages', BackendUtility::getRecord('pages', 1));
		$this->export->export_addRecord('pages', BackendUtility::getRecord('pages', 2));
		$this->export->export_addRecord('tt_content', BackendUtility::getRecord('tt_content', 1));

		$this->setPageTree(1, 1);

		// After adding ALL records we set relations:
		for ($a = 0; $a < 10; $a++) {
			$addR = $this->export->export_addDBRelations($a);
			if (!count($addR)) {
				break;
			}
		}

		$this->export->export_addFilesFromRelations();
		$this->export->export_addFilesFromSysFilesRecords();

		$out = $this->export->compileMemoryToFileContent('xml');

		$this->assertXmlStringEqualsXmlFile(__DIR__ . '/../../Fixtures/ImportExportXml/pages-and-ttcontent-with-rte-image-n-file-link.xml', $out);
	}

}