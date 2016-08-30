<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Import\PagesAndTtContentWithImages;

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
class ImportInFilledDatabaseTest extends \TYPO3\CMS\Impexp\Tests\Functional\Import\AbstractImportTestCase
{
    protected $additionalFoldersToCreate = [
        '/fileadmin/user_upload'
    ];

    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg' => 'fileadmin/user_upload/typo3_image2.jpg',
    ];

    protected $assertionDataSetDirectory = 'typo3/sysext/impexp/Tests/Functional/Import/PagesAndTtContentWithImages/DataSet/Assertion/';

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithDifferentImageToExistingData()
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/pages.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/tt_content-with-image.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_language.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_metadata.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_reference.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_storage.xml');

        $this->import->loadFile(__DIR__ . '/ImportExportXml/pages-and-ttcontent-with-existing-different-image.xml', 1);
        $this->import->importData(0);

        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image2.jpg';
        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image2_01.jpg';

        $this->assertAssertionDataSet('importPagesAndRelatedTtContentWithDifferentImageToExistingData');

        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');
        $this->assertFileEquals(__DIR__ . '/Folders/Assertion/fileadmin/user_upload/typo3_image2_01.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2_01.jpg');
    }

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithSameImageToExistingData()
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/pages.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/tt_content-with-image.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_language.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_metadata.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_reference.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/sys_file_storage.xml');

        $this->import->loadFile(__DIR__ . '/ImportExportXml/pages-and-ttcontent-with-existing-same-image.xml', 1);
        $this->import->importData(0);

        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image2.jpg';

        $this->assertAssertionDataSet('importPagesAndRelatedTtContentWithSameImageToExistingData');

        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');
    }
}
