<?php

declare(strict_types=1);

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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class ImagesWithStoragesTest extends AbstractImportExportTestCase
{
    protected function tearDown(): void
    {
        @unlink(Environment::getConfigPath() . '/system/file-storages.yaml');
        foreach (['fileadmin-1', 'fileadmin-3', 'fileadmin_invalid_path'] as $directory) {
            GeneralUtility::rmdir(Environment::getPublicPath() . '/' . $directory, true);
        }
        parent::tearDown();
    }

    #[Test]
    public function importImagesIntoLocalStoragesWithMatchingBasePath(): void
    {
        GeneralUtility::mkdir(Environment::getPublicPath() . '/fileadmin-1');
        GeneralUtility::mkdir(Environment::getPublicPath() . '/fileadmin-3');
        $this->get(FileStorageConfigurationWriter::class)->writeAll([
            1 => ['name' => 'fileadmin', 'default' => true, 'configuration' => ['basePath' => 'fileadmin/']],
            5 => ['name' => 'images one', 'configuration' => ['basePath' => 'fileadmin-1/']],
            6 => ['name' => 'images three', 'configuration' => ['basePath' => 'fileadmin-3/']],
        ]);

        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/images-with-storages.xml');
        $subject->importData();

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseAssertions/importImagesWithStorages.csv');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin-1/user_upload/typo3_image3.jpg');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin-3/user_upload/typo3_image2.jpg');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image5.jpg');
        self::assertSame(
            ['Files of storage 99 are imported into the default storage 1 "fileadmin".'],
            $subject->getWarningLog()
        );
        self::assertSame([], $subject->getErrorLog());
    }

    #[Test]
    public function importImagesOfUnknownStoragesIntoDefaultStorage(): void
    {
        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/images-with-storages.xml');
        $subject->importData();

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseAssertions/importImagesOfUnknownStorages.csv');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image3.jpg');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image5.jpg');
        self::assertDirectoryDoesNotExist(Environment::getPublicPath() . '/fileadmin-1');
        self::assertDirectoryDoesNotExist(Environment::getPublicPath() . '/fileadmin-3');
        self::assertSame(
            [
                'Files of storage 3 "fileadmin-3" (' . Environment::getPublicPath() . '/fileadmin-3/) are imported into the default storage 1 "fileadmin".',
                'Files of storage 1 "fileadmin-1" (' . Environment::getPublicPath() . '/fileadmin-1/) are imported into the default storage 1 "fileadmin".',
                'Files of storage 99 are imported into the default storage 1 "fileadmin".',
            ],
            $subject->getWarningLog()
        );
        self::assertSame([], $subject->getErrorLog());
    }

    #[Test]
    public function importImagesWithoutStorageRecordsIntoDefaultStorageInsteadOfStorageWithSameUid(): void
    {
        GeneralUtility::mkdir(Environment::getPublicPath() . '/fileadmin-3');
        $this->get(FileStorageConfigurationWriter::class)->writeAll([
            1 => ['name' => 'fileadmin', 'default' => true, 'configuration' => ['basePath' => 'fileadmin/']],
            3 => ['name' => 'unrelated', 'configuration' => ['basePath' => 'fileadmin-3/']],
        ]);

        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/images-without-storages.xml');
        $subject->importData();

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseAssertions/importImagesOfUnknownStorages.csv');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image3.jpg');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image5.jpg');
        self::assertDirectoryDoesNotExist(Environment::getPublicPath() . '/fileadmin-3/user_upload');
        self::assertSame(
            [
                'Files of storage 99 are imported into the default storage 1 "fileadmin".',
                'Files of storage 3 are imported into the default storage 1 "fileadmin".',
                'Files of storage 1 are imported into the default storage 1 "fileadmin".',
            ],
            $subject->getWarningLog()
        );
        self::assertSame([], $subject->getErrorLog());
    }

    #[Test]
    public function importImagesWithStaticAndFallbackStorages(): void
    {
        GeneralUtility::mkdir(Environment::getPublicPath() . '/fileadmin_invalid_path');
        $this->get(FileStorageConfigurationWriter::class)->writeAll([
            1 => ['name' => 'fileadminA', 'default' => true, 'configuration' => ['basePath' => 'fileadmin_invalid_path/']],
            2 => ['name' => 'fileadminB', 'configuration' => ['basePath' => 'fileadmin/']],
        ]);

        $subject = $this->get(Import::class);
        $subject->setPid(0);
        $subject->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/images-with-static-and-fallback-storages.xml');
        $subject->importData();

        self::assertFileExists(Environment::getPublicPath() . '/fileadmin_invalid_path/user_upload/typo3_image2.jpg');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin_invalid_path/user_upload/typo3_image3.jpg');
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin_invalid_path/user_upload/typo3_image5.jpg');
        self::assertFileExists(Environment::getPublicPath() . '/typo3conf/ext/template_extension/Resources/Public/Templates/Empty.html');
    }
}
