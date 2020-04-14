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
class PagesAndTtContentWithImagesInEmptyDatabaseTest extends AbstractImportExportTestCase
{
    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithImagesOnCaseSensitiveFilesystems()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        if (!$this->isCaseSensitiveFilesystem()) {
            self::markTestSkipped('Test not available on case insensitive filesystems.');
        }

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/pages-and-ttcontent-with-image.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importPagesAndRelatedTtContentWithImagesOnCaseSensitiveFilesystems.csv');

        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg');
    }

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithImagesOnCaseInsensitiveFilesystems()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        if ($this->isCaseSensitiveFilesystem()) {
            self::markTestSkipped('Test not available on case sensitive filesystems.');
        }

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/pages-and-ttcontent-with-image.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importPagesAndRelatedTtContentWithImagesOnCaseInsensitiveFilesystems.csv');

        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg');
    }

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithImagesButWithoutStorageOnCaseSensitiveFilesystems()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        if (!$this->isCaseSensitiveFilesystem()) {
            self::markTestSkipped('Test not available on case insensitive filesystems.');
        }

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/pages-and-ttcontent-with-image-without-storage.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importPagesAndRelatedTtContentWithImagesButWithoutStorageOnCaseSensitiveFilesystems.csv');

        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg');
    }

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithImagesButWithoutStorageOnCaseInsensitiveFilesystems()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        if ($this->isCaseSensitiveFilesystem()) {
            self::markTestSkipped('Test not available on case sensitive filesystems.');
        }

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/pages-and-ttcontent-with-image-without-storage.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importPagesAndRelatedTtContentWithImagesButWithoutStorageOnCaseInsensitiveFilesystems.csv');

        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg');
    }

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithImagesWithSpacesInPath()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/pages-and-ttcontent-with-image-with-spaces-in-path.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/folder_with_spaces/typo3_image2.jpg';
        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/folder_with_spaces/typo3_image3.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importPagesAndRelatedTtContentWithImagesWithSpacesInPath.csv');

        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/folder_with_spaces/typo3_image2.jpg');
        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image3.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/folder_with_spaces/typo3_image3.jpg');
    }

    /**
     * @test
     */
    public function importPagesAndRelatedTtContentWithImagesButNotIncluded()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            // Files are parallel to the fixture .xml file in a folder - impexp tests for /../ not allowed in path, so we set an absolute path here
            Environment::getFrameworkBasePath() . '/impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent-with-image-but-not-included.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importPagesAndRelatedTtContentWithImagesButNotIncluded.csv');

        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg');
    }

    /**
     * @test
     * @group not-mssql
     */
    public function importPagesAndRelatedTtContentWithImageWithForcedUids()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/pages-and-ttcontent-with-image-with-forced-uids.xml',
            1
        );
        $subject->force_all_UIDS = true;
        $subject->importData(0);

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importPagesAndRelatedTtContentWithImageWithForcedUids.csv');

        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg');

        $expectedErrors = [
                'Forcing uids of sys_file records is not supported! They will be imported as new records!'
        ];
        $errors = $subject->errorLog;
        self::assertSame($expectedErrors, $errors);
    }
}
