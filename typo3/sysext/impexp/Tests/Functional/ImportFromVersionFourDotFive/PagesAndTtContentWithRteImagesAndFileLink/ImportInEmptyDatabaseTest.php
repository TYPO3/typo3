<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\ImportFromVersionFourDotFive\PagesAndTtContentWithRteImagesAndFileLink;

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

/**
 * Functional test for the Import
 */
class ImportInEmptyDatabaseTest extends \TYPO3\CMS\Impexp\Tests\Functional\Import\AbstractImportTestCase
{
    protected $assertionDataSetDirectory = 'typo3/sysext/impexp/Tests/Functional/ImportFromVersionFourDotFive/PagesAndTtContentWithRteImagesAndFileLink/DataSet/Assertion/';

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        // Force the storage record to be caseSensitive "1" and prevent on-the-fly
        // storage creation which is dependant on the OS .
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_storage.xml');
    }

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithRteImagesAndFileLink()
    {
        $this->import->loadFile(__DIR__ . '/ImportExportXml/pages-and-ttcontent-with-rte-image-n-file-link.xml', 1);
        $this->import->importData(0);

        $this->assertAssertionDataSet('importPagesAndRelatedTtContentWithRteImagesAndFileLink');

        $this->assertFileExists(PATH_site . 'fileadmin/user_upload/_imported/uploads/RTEmagicC_typo3_image2.jpg.jpg');
        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/_imported/RTEmagicP_typo3_image2.jpg');
        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image3.jpg', PATH_site . 'fileadmin/user_upload/_imported/fileadmin/user_upload/typo3_image3.jpg');
        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/uploads/tx_impexpgroupfiles/typo3_image4.jpg', PATH_site . 'fileadmin/user_upload/_imported/fileadmin/user_upload/typo3_image4.jpg');
    }
}
