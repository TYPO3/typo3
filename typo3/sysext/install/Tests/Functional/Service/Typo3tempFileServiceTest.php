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
use TYPO3\CMS\Install\Service\Typo3tempFileService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class Typo3tempFileServiceTest extends FunctionalTestCase
{
    /**
     * @var string
     */
    private $directoryName;

    /**
     * @var string
     */
    private $directoryPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directoryName = uniqid('test');
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
    public function clearAssetsFolderDetectsNonExistingFolder()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501781454);

        $processedFileRepository = $this->prophesize(ProcessedFileRepository::class);
        $storageRepository = $this->prophesize(StorageRepository::class);
        $subject = new Typo3tempFileService($processedFileRepository->reveal(), $storageRepository->reveal());
        $subject->clearAssetsFolder('/typo3temp/assets/' . $this->directoryName);
    }

    /**
     * @test
     */
    public function clearAssetsFolderClearsFolder()
    {
        GeneralUtility::mkdir_deep($this->directoryPath . '/a/b');
        file_put_contents($this->directoryPath . '/c.css', '/* test */');
        file_put_contents($this->directoryPath . '/a/b/c.css', '/* test */');
        file_put_contents($this->directoryPath . '/a/b/d.css', '/* test */');

        $processedFileRepository = $this->prophesize(ProcessedFileRepository::class);
        $storageRepository = $this->prophesize(StorageRepository::class);
        $subject = new Typo3tempFileService($processedFileRepository->reveal(), $storageRepository->reveal());
        $subject->clearAssetsFolder('/typo3temp/assets/' . $this->directoryName);

        self::assertDirectoryNotExists($this->directoryPath . '/a');
        self::assertDirectoryExists($this->directoryPath);
        self::assertFileNotExists($this->directoryPath . '/c.css');
    }
}
