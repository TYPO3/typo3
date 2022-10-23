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

namespace TYPO3\CMS\Install\Tests\Functional\Service;

use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\Service\Typo3tempFileService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class Typo3tempFileServiceTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;
    private string $directoryName;
    private string $directoryPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directoryName = StringUtility::getUniqueId('test');
        $this->directoryPath = $this->instancePath . '/typo3temp/assets/' . $this->directoryName;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        GeneralUtility::rmdir($this->directoryPath, true);
        unset($this->directoryName, $this->directoryPath);
    }

    /**
     * @test
     */
    public function clearAssetsFolderDetectsNonExistingFolder(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501781454);

        $processedFileRepository = $this->getMockBuilder(ProcessedFileRepository::class)->disableOriginalConstructor()->getMock();
        $storageRepository = $this->getMockBuilder(StorageRepository::class)->disableOriginalConstructor()->getMock();
        $subject = new Typo3tempFileService($processedFileRepository, $storageRepository);
        $subject->clearAssetsFolder('/typo3temp/assets/' . $this->directoryName);
    }

    /**
     * @test
     */
    public function clearAssetsFolderClearsFolder(): void
    {
        GeneralUtility::mkdir_deep($this->directoryPath . '/a/b');
        file_put_contents($this->directoryPath . '/c.css', '/* test */');
        file_put_contents($this->directoryPath . '/a/b/c.css', '/* test */');
        file_put_contents($this->directoryPath . '/a/b/d.css', '/* test */');

        $processedFileRepository = $this->getMockBuilder(ProcessedFileRepository::class)->disableOriginalConstructor()->getMock();
        $storageRepository = $this->getMockBuilder(StorageRepository::class)->disableOriginalConstructor()->getMock();
        $subject = new Typo3tempFileService($processedFileRepository, $storageRepository);
        $subject->clearAssetsFolder('/typo3temp/assets/' . $this->directoryName);

        self::assertDirectoryDoesNotExist($this->directoryPath . '/a');
        self::assertDirectoryExists($this->directoryPath);
        self::assertFileDoesNotExist($this->directoryPath . '/c.css');
    }
}
