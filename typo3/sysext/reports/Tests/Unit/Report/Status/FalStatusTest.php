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

namespace TYPO3\CMS\Reports\Tests\Unit\Report\Status;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\InvalidHashException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\MimeTypeDetector;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Service\ResourceConsistencyService;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\Report\Status\FalStatus;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FalStatusTest extends UnitTestCase
{
    #[Test]
    public function consistencyCheckReportsFolderWhichCannotBeRead(): void
    {
        $storage = self::createStub(ResourceStorage::class);
        $storage->method('isBrowsable')->willReturn(true);
        $storage->method('getUid')->willReturn(1);
        $storage->method('getName')->willReturn('fileadmin');

        $subFolder = self::createStub(Folder::class);
        $subFolder->method('getStorage')->willReturn($storage);
        $subFolder->method('getIdentifier')->willReturn('/broken/');
        $subFolder->method('getFiles')->willThrowException(
            new InvalidHashException('Hash has to be non-empty string.', 1551950301)
        );
        $subFolder->method('getSubfolders')->willReturn([]);

        $rootFolder = self::createStub(Folder::class);
        $rootFolder->method('getStorage')->willReturn($storage);
        $rootFolder->method('getIdentifier')->willReturn('/');
        $rootFolder->method('getFiles')->willReturn([]);
        $rootFolder->method('getSubfolders')->willReturn([$subFolder]);
        $storage->method('getRootLevelFolder')->willReturn($rootFolder);

        $storageRepository = self::createStub(StorageRepository::class);
        $storageRepository->method('findAll')->willReturn([$storage]);

        $subject = new FalStatus(
            new ResourceConsistencyService(new Random(), new Features(), new MimeTypeDetector()),
            self::createStub(ConnectionPool::class),
            $storageRepository,
        );

        $status = $subject->getDetailedStatus()['ConsistencyCheck'];

        self::assertSame(ContextualFeedbackSeverity::ERROR, $status->getSeverity());
        self::assertStringContainsString('1:/broken/', $status->getMessage());
        self::assertStringContainsString('Hash has to be non-empty string.', $status->getMessage());
    }
}
