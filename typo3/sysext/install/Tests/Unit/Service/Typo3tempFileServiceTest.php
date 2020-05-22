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

namespace TYPO3\CMS\Install\Tests\Unit\Service;

use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Install\Service\Typo3tempFileService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Typo3tempFileServiceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function clearAssetsFolderThrowsWithInvalidPath()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501781453);
        $processedFileRepository = $this->prophesize(ProcessedFileRepository::class);
        $storageRepository = $this->prophesize(StorageRepository::class);
        $subject = new Typo3tempFileService($processedFileRepository->reveal(), $storageRepository->reveal());
        $subject->clearAssetsFolder('../foo');
    }

    /**
     * @test
     */
    public function clearAssetsFolderThrowsIfPathDoesNotStartWithTypotempAssets()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501781453);
        $processedFileRepository = $this->prophesize(ProcessedFileRepository::class);
        $storageRepository = $this->prophesize(StorageRepository::class);
        $subject = new Typo3tempFileService($processedFileRepository->reveal(), $storageRepository->reveal());
        $subject->clearAssetsFolder('typo3temp/foo');
    }
}
