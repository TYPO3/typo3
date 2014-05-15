<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Import\PagesAndTtContentWithImages;

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

	protected $assertionDataSetDirectory = 'typo3/sysext/impexp/Tests/Functional/Import/PagesAndTtContentWithImages/DataSet/Assertion/';

	/**
	 * @test
	 */
	public function importPagesAndRelatedTtContentWithImagesOnCaseSensitiveFilesystems() {
		if (!$this->isCaseSensitiveFilesystem()) {
			$this->markTestSkipped('Test not available on case insensitive filesystems.');
		}

		$this->import->loadFile(__DIR__ . '/../../Fixtures/ImportExportXml/pages-and-ttcontent-with-image.xml', 1);
		$this->import->importData(0);

		$this->assertAssertionDataSet('importPagesAndRelatedTtContentWithImagesOnCaseSensitiveFilesystems');

		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');

	}

	/**
	 * @test
	 */
	public function importPagesAndRelatedTtContentWithImagesOnCaseInsensitiveFilesystems() {
		if ($this->isCaseSensitiveFilesystem()) {
			$this->markTestSkipped('Test not available on case sensitive filesystems.');
		}

		$this->import->loadFile(__DIR__ . '/../../Fixtures/ImportExportXml/pages-and-ttcontent-with-image.xml', 1);
		$this->import->importData(0);

		$this->assertAssertionDataSet('importPagesAndRelatedTtContentWithImagesOnCaseInsensitiveFilesystems');

		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');

	}

	/**
	 * @test
	 */
	public function importPagesAndRelatedTtContentWithImagesButWithoutStorageOnCaseSensitiveFilesystems() {
		if (!$this->isCaseSensitiveFilesystem()) {
			$this->markTestSkipped('Test not available on case insensitive filesystems.');
		}

		$this->import->loadFile(__DIR__ . '/ImportExportXml/pages-and-ttcontent-with-image-without-storage.xml', 1);
		$this->import->importData(0);

		$this->assertAssertionDataSet('importPagesAndRelatedTtContentWithImagesButWithoutStorageOnCaseSensitiveFilesystems');

		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');
	}

	/**
	 * @test
	 */
	public function importPagesAndRelatedTtContentWithImagesButWithoutStorageOnCaseInsensitiveFilesystems() {
		if ($this->isCaseSensitiveFilesystem()) {
			$this->markTestSkipped('Test not available on case sensitive filesystems.');
		}

		$this->import->loadFile(__DIR__ . '/ImportExportXml/pages-and-ttcontent-with-image-without-storage.xml', 1);
		$this->import->importData(0);

		$this->assertAssertionDataSet('importPagesAndRelatedTtContentWithImagesButWithoutStorageOnCaseInsensitiveFilesystems');

		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');
	}

	/**
	 * @test
	 */
	public function importPagesAndRelatedTtContentWithImagesWithSpacesInPath() {

		$this->import->loadFile(__DIR__ . '/ImportExportXml/pages-and-ttcontent-with-image-with-spaces-in-path.xml', 1);
		$this->import->importData(0);

		$this->assertAssertionDataSet('importPagesAndRelatedTtContentWithImagesWithSpacesInPath');

		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/folder_with_spaces/typo3_image2.jpg');
		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image3.jpg', PATH_site . 'fileadmin/user_upload/folder_with_spaces/typo3_image3.jpg');
	}

	/**
	 * @test
	 */
	public function importPagesAndRelatedTtContentWithImageWithForcedUids() {

		$this->import->loadFile(__DIR__ . '/ImportExportXml/pages-and-ttcontent-with-image-with-forced-uids.xml', 1);
		$this->import->force_all_UIDS = TRUE;
		$this->import->importData(0);

		$this->assertAssertionDataSet('importPagesAndRelatedTtContentWithImageWithForcedUids');

		$this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');

		$expectedErrors = array(
			'Forcing uids of sys_file records is not supported! They will be imported as new records!'
		);
		$errors = $this->import->errorLog;
		$this->assertSame($expectedErrors, $errors);
	}

}
