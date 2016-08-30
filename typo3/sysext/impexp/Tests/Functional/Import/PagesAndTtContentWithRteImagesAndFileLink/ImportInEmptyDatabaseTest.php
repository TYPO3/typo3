<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Import\PagesAndTtContentWithRteImagesAndFileLink;

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
    /**
     * @var array
     */
    protected $additionalFoldersToCreate = [
        '/fileadmin/_processed_'
    ];

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/impexp/Tests/Functional/Import/PagesAndTtContentWithRteImagesAndFileLink/DataSet/Assertion/';

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithRteImagesAndFileLink()
    {
        $this->import->loadFile(__DIR__ . '/../../Fixtures/ImportExportXml/pages-and-ttcontent-with-rte-image-n-file-link.xml', 1);
        $this->import->importData(0);

        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image2.jpg';
        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image3.jpg';

        $this->assertAssertionDataSet('importPagesAndRelatedTtContentWithRteImagesAndFileLink');

        $this->assertFileNotExists(PATH_site . 'fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg');

        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', PATH_site . 'fileadmin/user_upload/typo3_image2.jpg');
        $this->assertFileEquals(__DIR__ . '/../../Fixtures/Folders/fileadmin/user_upload/typo3_image3.jpg', PATH_site . 'fileadmin/user_upload/typo3_image3.jpg');
    }
}
