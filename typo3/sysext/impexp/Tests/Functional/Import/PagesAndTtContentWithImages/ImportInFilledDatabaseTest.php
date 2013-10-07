<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Import\PagesAndTtContentWithImages;

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

require_once __DIR__ . '/../AbstractImportTestCase.php';

/**
 * Functional test for the ImportExport
 */
class ImportInFilledDatabaseTest extends \TYPO3\CMS\Impexp\Tests\Functional\Import\AbstractImportTestCase {

	protected $additionalFoldersToCreate = array(
		'/fileadmin/user_upload'
	);

	protected $pathsToLinkInTestInstance = array(
		'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg' => 'fileadmin/user_upload/typo3_image2.jpg',
	);

	protected $assertionDataSetDirectory = 'typo3/sysext/impexp/Tests/Functional/Import/PagesAndTtContentWithImages/DataSet/Assertion/';

	/**
	 * @test
	 */
	public function importPagesAndRelatedTtContentWithDifferentImageToExistingData() {

		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/pages.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/tt_content-with-image.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_language.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_metadata.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_reference.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_storage.xml');

		$this->import->loadFile(__DIR__ . '/ImportExportXml/pages-and-ttcontent-with-existing-different-image.xml', 1);
		$this->import->importData(0);

		$this->assertAssertionDataSet('importPagesAndRelatedTtContentWithDifferentImageToExistingData');

		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');
		$this->assertFileEquals(__DIR__ . '/Folders/Assertion/fileadmin/user_upload/typo3_image2_01.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2_01.jpg');

	}

	/**
	 * @test
	 */
	public function importPagesAndRelatedTtContentWithSameImageToExistingData() {

		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/pages.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/tt_content-with-image.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_language.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_metadata.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_reference.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_storage.xml');

		$this->import->loadFile(__DIR__ . '/ImportExportXml/pages-and-ttcontent-with-existing-same-image.xml', 1);
		$this->import->importData(0);

		$this->assertAssertionDataSet('importPagesAndRelatedTtContentWithSameImageToExistingData');

		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');

	}


}