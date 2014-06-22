<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Import\GroupFileAndFileReferenceItemInFlexForm;

/**
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

require_once __DIR__ . '/../AbstractImportTestCase.php';

/**
 * Functional test for the ImportExport
 */
class ImportInEmptyDatabaseTest extends \TYPO3\CMS\Impexp\Tests\Functional\Import\AbstractImportTestCase {

	/**
	 * @var array
	 */
	protected $additionalFoldersToCreate = array(
		'/uploads/tx_impexpgroupfiles'
	);
	/**
	 * @var array
	 */
	protected $testExtensionsToLoad = array(
		'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/impexp_group_files'
	);

	/**
	 * @var string
	 */
	protected $assertionDataSetDirectory = 'typo3/sysext/impexp/Tests/Functional/Import/GroupFileAndFileReferenceItemInFlexForm/DataSet/Assertion/';

	/**
	 * @test
	 */
	public function importGroupFileAndFileReferenceItemInFlexForm() {

		$this->import->loadFile(__DIR__ . '/../../Fixtures/ImportExportXml/impexp-group-file-and-file_reference-item-in-ff.xml', 1);
		$this->import->importData(0);

		$this->assertAssertionDataSet('importGroupFileAndFileReferenceItemInFlexForm');

		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image3.jpg', PATH_site . 'fileadmin/user_upload/typo3_image3.jpg');
		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image5.jpg', PATH_site . 'fileadmin/user_upload/typo3_image5.jpg');
		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/uploads/tx_impexpgroupfiles/typo3_image4.jpg', PATH_site . 'uploads/tx_impexpgroupfiles/typo3_image4.jpg');
	}

}