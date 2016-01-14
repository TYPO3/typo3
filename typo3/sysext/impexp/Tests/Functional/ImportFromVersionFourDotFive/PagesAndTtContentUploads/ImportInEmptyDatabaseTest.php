<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\ImportFromVersionFourDotFive\PagesAndTtContentUploads;

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
    protected $assertionDataSetDirectory = 'typo3/sysext/impexp/Tests/Functional/ImportFromVersionFourDotFive/PagesAndTtContentUploads/DataSet/Assertion/';

    /**
     * @test
     */
    public function importPagesAndTtContentUploads()
    {
        $this->import->loadFile(__DIR__ . '/ImportExportXml/pages-and-ttcontent-uploads.xml', 1);
        $this->import->importData(0);

        $this->assertAssertionDataSet('importPagesAndTtContentUploads');

        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/_imported/typo3_image2.jpg');
        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image3.jpg', PATH_site . 'fileadmin/user_upload/_imported/typo3_image3.jpg');
    }
}
