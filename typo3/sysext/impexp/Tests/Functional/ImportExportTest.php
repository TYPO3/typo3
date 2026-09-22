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

namespace TYPO3\CMS\Impexp\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Import;

final class ImportExportTest extends AbstractImportExportTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mm',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mnsymmetric',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mnattributeinline',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mnattributesimple',
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/template_extension',
    ];

    protected function tearDown(): void
    {
        @unlink(Environment::getConfigPath() . '/system/file-storages.yaml');
        foreach (['media', 'other'] as $directory) {
            GeneralUtility::rmdir(Environment::getPublicPath() . '/' . $directory, true);
        }
        parent::tearDown();
    }

    #[Test]
    public function importExportPingPongSucceeds(): void
    {
        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile('EXT:impexp/Tests/Functional/Fixtures/XmlImports/irre-records.xml');
        $import->setForceAllUids(true);
        $import->importData();

        $export = $this->get(Export::class);
        $export->setPid(1);
        $export->setLevels(Export::LEVELS_INFINITE);
        $export->setTables(['_ALL']);
        $export->setRelOnlyTables(['_ALL']);
        $export->process();
        $actual = $export->render();

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/Fixtures/XmlImports/irre-records.xml',
            $actual
        );
    }

    #[Test]
    public function importExportKeepsSchemeOfMailtoAndTelephoneLinks(): void
    {
        $bodytext = '<p><a href="mailto:info@example.org">Write us</a><br /> <a href="tel:0123456789">Call us</a></p>';

        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/tt_content-with-mailto-and-tel-links.csv');

        $export = $this->get(Export::class);
        $export->setPid(1);
        $export->setLevels(1);
        $export->setTables(['_ALL']);
        $export->process();

        $exportFile = Environment::getPublicPath() . '/fileadmin/xml_exports/mailto-and-tel-links.xml';
        GeneralUtility::mkdir_deep(dirname($exportFile));
        GeneralUtility::writeFile($exportFile, $export->render(), true);
        $this->testFilesToDelete[] = $exportFile;

        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile('fileadmin/xml_exports/mailto-and-tel-links.xml');
        $import->importData();

        $importedBodytext = $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->executeQuery('SELECT bodytext FROM tt_content WHERE uid > 1 ORDER BY uid ASC')
            ->fetchOne();

        self::assertSame($bodytext, $importedBodytext);
    }

    #[Test]
    public function exportedFilesAreImportedIntoStorageWithSameBasePathInsteadOfStorageWithSameUid(): void
    {
        $exportFile = $this->exportFileOfMediaStorage();
        $this->get(FileStorageConfigurationWriter::class)->apply([
            2 => ['name' => 'Other', 'configuration' => ['basePath' => 'other/']],
            3 => ['name' => 'Media', 'configuration' => ['basePath' => 'media/']],
        ]);

        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile($exportFile);
        $import->importData();

        self::assertFileExists(Environment::getPublicPath() . '/media/user_upload/typo3_image2.jpg');
        self::assertFileDoesNotExist(Environment::getPublicPath() . '/other/user_upload/typo3_image2.jpg');
        self::assertSame([3], $this->getStoragesOfFiles());
        self::assertSame([], $import->getWarningLog());
        self::assertSame([], $import->getErrorLog());
    }

    #[Test]
    public function exportedFilesOfUnknownBasePathAreImportedIntoDefaultStorage(): void
    {
        $exportFile = $this->exportFileOfMediaStorage();
        $this->get(FileStorageConfigurationWriter::class)->apply([
            2 => ['name' => 'Other', 'configuration' => ['basePath' => 'other/']],
        ]);
        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg';

        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile($exportFile);
        $import->importData();

        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg');
        self::assertFileDoesNotExist(Environment::getPublicPath() . '/other/user_upload/typo3_image2.jpg');
        self::assertSame([1], $this->getStoragesOfFiles());
        self::assertSame(
            ['Files of storage 2 "Media" (' . Environment::getPublicPath() . '/media/) are imported into the default storage 1 "fileadmin".'],
            $import->getWarningLog()
        );
        self::assertSame([], $import->getErrorLog());
    }

    /**
     * Exports a file of the local storage 2 "Media" and removes the file and its
     * record afterwards, so that the import has to write it again.
     */
    private function exportFileOfMediaStorage(): string
    {
        $publicPath = Environment::getPublicPath();
        GeneralUtility::mkdir_deep($publicPath . '/media/user_upload');
        GeneralUtility::mkdir($publicPath . '/other');
        copy(__DIR__ . '/Fixtures/Folders/fileadmin/user_upload/typo3_image2.jpg', $publicPath . '/media/user_upload/typo3_image2.jpg');
        $this->get(FileStorageConfigurationWriter::class)->writeAll([
            1 => ['name' => 'fileadmin', 'default' => true, 'configuration' => ['basePath' => 'fileadmin/']],
            2 => ['name' => 'Media', 'configuration' => ['basePath' => 'media/']],
        ]);
        $file = $this->get(StorageRepository::class)->findByUid(2)->getFile('/user_upload/typo3_image2.jpg');

        $export = $this->get(Export::class);
        $export->setRecord(['sys_file:' . $file->getUid()]);
        $export->process();
        $exportFile = $publicPath . '/fileadmin/xml_exports/media-storage.xml';
        GeneralUtility::mkdir_deep(dirname($exportFile));
        GeneralUtility::writeFile($exportFile, $export->render(), true);
        $this->testFilesToDelete[] = $exportFile;

        unlink($publicPath . '/media/user_upload/typo3_image2.jpg');
        $this->getConnectionPool()->getConnectionForTable('sys_file')->truncate('sys_file');
        return $exportFile;
    }

    /**
     * @return list<int>
     */
    private function getStoragesOfFiles(): array
    {
        return array_map(
            intval(...),
            $this->getConnectionPool()
                ->getConnectionForTable('sys_file')
                ->executeQuery('SELECT storage FROM sys_file')
                ->fetchFirstColumn()
        );
    }
}
