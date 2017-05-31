<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Import;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

/**
 * Test case
 */
class PagesAndTtContentWithImagesInFilledDatabaseTest extends AbstractImportExportTestCase
{
    /**
     * @var array
     */
    protected $additionalFoldersToCreate = [
        '/fileadmin/user_upload'
    ];

    /**
     * @var array
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg' => 'fileadmin/user_upload/typo3_image2.jpg',
    ];

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithDifferentImageToExistingData()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tt_content-with-image.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_language.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_metadata.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_reference.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_storage.xml');

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/pages-and-ttcontent-with-existing-different-image.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image2.jpg';
        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image2_01.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importPagesAndRelatedTtContentWithDifferentImageToExistingData.csv');

        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');
        $this->assertFileEquals(__DIR__ . '/../Fixtures/FileAssertions/typo3_image2_01.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2_01.jpg');
    }

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithSameImageToExistingData()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/tt_content-with-image.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_language.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_metadata.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_reference.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/sys_file_storage.xml');

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/pages-and-ttcontent-with-existing-same-image.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image2.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importPagesAndRelatedTtContentWithSameImageToExistingData.csv');

        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');
    }
}
