<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\ImportFromVersionFourDotFive\PagesAndTtContentUploads;

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

require_once __DIR__ . '/../../Import/AbstractImportTestCase.php';

/**
 * Functional test for the ImportExport
 */
class ImportInEmptyDatabaseTest extends \TYPO3\CMS\Impexp\Tests\Functional\Import\AbstractImportTestCase {

	protected $assertionDataSetDirectory = 'typo3/sysext/impexp/Tests/Functional/ImportFromVersionFourDotFive/PagesAndTtContentUploads/DataSet/Assertion/';

	/**
	 * @test
	 */
	public function importPagesAndTtContentUploads() {
		$this->import->loadFile(__DIR__ . '/ImportExportXml/pages-and-ttcontent-uploads.xml', 1);
		$this->import->importData(0);

		$this->assertAssertionDataSet('importPagesAndTtContentUploads');

		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/_imported/typo3_image2.jpg');
		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image3.jpg', PATH_site . 'fileadmin/user_upload/_imported/typo3_image3.jpg');
	}

}