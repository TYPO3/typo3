<?php

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

namespace TYPO3\CMS\Impexp\Tests\Functional\Import;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

/**
 * Test case
 */
class PagesAndTtContentWithRteImagesAndFileLinkTest extends AbstractImportExportTestCase
{
    /**
     * @var array
     */
    protected $additionalFoldersToCreate = [
        '/fileadmin/_processed_'
    ];

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithRteImagesAndFileLink()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/pages-and-ttcontent-with-rte-image-n-file-link.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg';
        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image3.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importPagesAndRelatedTtContentWithRteImagesAndFileLink.csv');

        self::assertFileNotExists(Environment::getPublicPath() . '/fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg');

        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg');
        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image3.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image3.jpg');
    }
}
