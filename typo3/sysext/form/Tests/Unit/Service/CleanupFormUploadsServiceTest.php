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

namespace TYPO3\CMS\Form\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Form\Service\CleanupFormUploadsService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CleanupFormUploadsServiceTest extends UnitTestCase
{
    private ResourceFactory&MockObject $resourceFactoryMock;
    private CleanupFormUploadsService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resourceFactoryMock = $this->createMock(ResourceFactory::class);
        $this->subject = new CleanupFormUploadsService(
            $this->resourceFactoryMock,
            $this->createMock(LoggerInterface::class),
        );
    }

    #[Test]
    public function getExpiredFoldersReturnsEmptyArrayWhenNoFoldersExist(): void
    {
        $parentFolder = $this->createMock(Folder::class);
        $parentFolder->method('getSubfolders')->willReturn([]);

        $this->resourceFactoryMock
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('1:/user_upload/')
            ->willReturn($parentFolder);

        $result = $this->subject->getExpiredFolders(86400, ['1:/user_upload/']);

        self::assertSame([], $result);
    }

    #[Test]
    public function getExpiredFoldersIgnoresNonFormFolders(): void
    {
        $regularFolder = $this->createMock(Folder::class);
        $regularFolder->method('getName')->willReturn('my_custom_folder');
        $regularFolder->method('getModificationTime')->willReturn(time() - 172800);

        $parentFolder = $this->createMock(Folder::class);
        $parentFolder->method('getSubfolders')->willReturn([$regularFolder]);

        $this->resourceFactoryMock
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('1:/user_upload/')
            ->willReturn($parentFolder);

        $result = $this->subject->getExpiredFolders(86400, ['1:/user_upload/']);

        self::assertSame([], $result);
    }

    #[Test]
    public function getExpiredFoldersIgnoresFormFolderWithIncorrectHashLength(): void
    {
        // form_ + only 10 hex chars (should be 40)
        $shortHashFolder = $this->createMock(Folder::class);
        $shortHashFolder->method('getName')->willReturn('form_abcdef1234');
        $shortHashFolder->method('getModificationTime')->willReturn(time() - 172800);

        $parentFolder = $this->createMock(Folder::class);
        $parentFolder->method('getSubfolders')->willReturn([$shortHashFolder]);

        $this->resourceFactoryMock
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('1:/user_upload/')
            ->willReturn($parentFolder);

        $result = $this->subject->getExpiredFolders(86400, ['1:/user_upload/']);

        self::assertSame([], $result);
    }

    #[Test]
    public function getExpiredFoldersIgnoresFormFoldersYoungerThanRetentionPeriod(): void
    {
        // Valid form folder name but very recent
        $recentFolder = $this->createMock(Folder::class);
        $recentFolder->method('getName')->willReturn('form_' . str_repeat('a', 40));
        $recentFolder->method('getModificationTime')->willReturn(time() - 3600); // 1 hour ago

        $parentFolder = $this->createMock(Folder::class);
        $parentFolder->method('getSubfolders')->willReturn([$recentFolder]);

        $this->resourceFactoryMock
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('1:/user_upload/')
            ->willReturn($parentFolder);

        // Retention of 24 hours
        $result = $this->subject->getExpiredFolders(86400, ['1:/user_upload/']);

        self::assertSame([], $result);
    }

    #[Test]
    public function getExpiredFoldersFindsOldFormFolders(): void
    {
        // Valid form folder name and old enough
        $oldFolder = $this->createMock(Folder::class);
        $oldFolder->method('getName')->willReturn('form_' . str_repeat('b', 40));
        $oldFolder->method('getModificationTime')->willReturn(time() - 172800); // 48 hours ago

        $parentFolder = $this->createMock(Folder::class);
        $parentFolder->method('getSubfolders')->willReturn([$oldFolder]);

        $this->resourceFactoryMock
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('1:/user_upload/')
            ->willReturn($parentFolder);

        // Retention of 24 hours
        $result = $this->subject->getExpiredFolders(86400, ['1:/user_upload/']);

        self::assertCount(1, $result);
        self::assertSame($oldFolder, $result[0]);
    }

    #[Test]
    public function getExpiredFoldersMixedFoldersReturnsOnlyExpired(): void
    {
        // Old valid form folder (should be returned)
        $oldFormFolder = $this->createMock(Folder::class);
        $oldFormFolder->method('getName')->willReturn('form_' . str_repeat('c', 40));
        $oldFormFolder->method('getModificationTime')->willReturn(time() - 172800);

        // Recent valid form folder (should NOT be returned)
        $recentFormFolder = $this->createMock(Folder::class);
        $recentFormFolder->method('getName')->willReturn('form_' . str_repeat('d', 40));
        $recentFormFolder->method('getModificationTime')->willReturn(time() - 1800); // 30 min ago

        // Regular folder (should NOT be returned)
        $regularFolder = $this->createMock(Folder::class);
        $regularFolder->method('getName')->willReturn('user_upload');
        $regularFolder->method('getModificationTime')->willReturn(time() - 172800);

        $parentFolder = $this->createMock(Folder::class);
        $parentFolder->method('getSubfolders')->willReturn([
            $oldFormFolder,
            $recentFormFolder,
            $regularFolder,
        ]);

        $this->resourceFactoryMock
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('1:/user_upload/')
            ->willReturn($parentFolder);

        $result = $this->subject->getExpiredFolders(86400, ['1:/user_upload/']);

        self::assertCount(1, $result);
        self::assertSame($oldFormFolder, $result[0]);
    }

    #[Test]
    public function getExpiredFoldersWithSpecificUploadFolderScansOnlyThatFolder(): void
    {
        $oldFormFolder = $this->createMock(Folder::class);
        $oldFormFolder->method('getName')->willReturn('form_' . str_repeat('e', 40));
        $oldFormFolder->method('getModificationTime')->willReturn(time() - 172800);

        $parentFolder = $this->createMock(Folder::class);
        $parentFolder->method('getSubfolders')->willReturn([$oldFormFolder]);

        $this->resourceFactoryMock
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('1:/user_upload/')
            ->willReturn($parentFolder);

        $result = $this->subject->getExpiredFolders(86400, ['1:/user_upload/']);

        self::assertCount(1, $result);
        self::assertSame($oldFormFolder, $result[0]);
    }

    #[Test]
    public function getExpiredFoldersWithMultipleUploadFoldersScansAllOfThem(): void
    {
        $folder1 = $this->createMock(Folder::class);
        $folder1->method('getName')->willReturn('form_' . str_repeat('f', 40));
        $folder1->method('getModificationTime')->willReturn(time() - 172800);

        $parentFolder1 = $this->createMock(Folder::class);
        $parentFolder1->method('getSubfolders')->willReturn([$folder1]);

        $folder2 = $this->createMock(Folder::class);
        $folder2->method('getName')->willReturn('form_' . str_repeat('a', 40));
        $folder2->method('getModificationTime')->willReturn(time() - 172800);

        $parentFolder2 = $this->createMock(Folder::class);
        $parentFolder2->method('getSubfolders')->willReturn([$folder2]);

        $this->resourceFactoryMock
            ->method('getFolderObjectFromCombinedIdentifier')
            ->willReturnCallback(function (string $id) use ($parentFolder1, $parentFolder2) {
                return match ($id) {
                    '1:/user_upload/' => $parentFolder1,
                    '2:/custom_uploads/' => $parentFolder2,
                    default => throw new \InvalidArgumentException('Unexpected folder identifier: ' . $id, 1741168800),
                };
            });

        $result = $this->subject->getExpiredFolders(86400, ['1:/user_upload/', '2:/custom_uploads/']);

        self::assertCount(2, $result);
    }

    #[Test]
    public function deleteFoldersReturnsCorrectSummaryOnSuccess(): void
    {
        $folder1 = $this->createMock(Folder::class);
        $folder1->expects($this->once())->method('delete')->with(true);
        $folder1->method('getCombinedIdentifier')->willReturn('1:/user_upload/form_aaa/');

        $folder2 = $this->createMock(Folder::class);
        $folder2->expects($this->once())->method('delete')->with(true);
        $folder2->method('getCombinedIdentifier')->willReturn('1:/user_upload/form_bbb/');

        $result = $this->subject->deleteFolders([$folder1, $folder2]);

        self::assertSame(2, $result['deleted']);
        self::assertSame(0, $result['failed']);
        self::assertSame([], $result['errors']);
    }

    #[Test]
    public function deleteFoldersHandlesPartialFailure(): void
    {
        $folder1 = $this->createMock(Folder::class);
        $folder1->method('delete')->willThrowException(new \RuntimeException('Access denied'));
        $folder1->method('getCombinedIdentifier')->willReturn('1:/user_upload/form_aaa/');

        $folder2 = $this->createMock(Folder::class);
        $folder2->expects($this->once())->method('delete')->with(true);
        $folder2->method('getCombinedIdentifier')->willReturn('1:/user_upload/form_bbb/');

        $result = $this->subject->deleteFolders([$folder1, $folder2]);

        self::assertSame(1, $result['deleted']);
        self::assertSame(1, $result['failed']);
        self::assertCount(1, $result['errors']);
        self::assertSame('1:/user_upload/form_aaa/', $result['errors'][0]['folder']);
        self::assertSame('Access denied', $result['errors'][0]['message']);
    }

    #[Test]
    public function deleteFoldersReturnsZeroSummaryForEmptyInput(): void
    {
        $result = $this->subject->deleteFolders([]);

        self::assertSame(0, $result['deleted']);
        self::assertSame(0, $result['failed']);
        self::assertSame([], $result['errors']);
    }

    #[Test]
    public function getExpiredFoldersOnlyMatchesExact40HexCharPattern(): void
    {
        // form_ + 40 chars but with non-hex char 'g'
        $invalidHexFolder = $this->createMock(Folder::class);
        $invalidHexFolder->method('getName')->willReturn('form_' . str_repeat('g', 40));
        $invalidHexFolder->method('getModificationTime')->willReturn(time() - 172800);

        // form_ + 39 hex chars (too short)
        $tooShortFolder = $this->createMock(Folder::class);
        $tooShortFolder->method('getName')->willReturn('form_' . str_repeat('a', 39));
        $tooShortFolder->method('getModificationTime')->willReturn(time() - 172800);

        // form_ + 41 hex chars (too long)
        $tooLongFolder = $this->createMock(Folder::class);
        $tooLongFolder->method('getName')->willReturn('form_' . str_repeat('a', 41));
        $tooLongFolder->method('getModificationTime')->willReturn(time() - 172800);

        // Valid: form_ + 40 hex chars
        $validFolder = $this->createMock(Folder::class);
        $validFolder->method('getName')->willReturn('form_0123456789abcdef0123456789abcdef01234567');
        $validFolder->method('getModificationTime')->willReturn(time() - 172800);

        $parentFolder = $this->createMock(Folder::class);
        $parentFolder->method('getSubfolders')->willReturn([
            $invalidHexFolder,
            $tooShortFolder,
            $tooLongFolder,
            $validFolder,
        ]);

        $this->resourceFactoryMock
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('1:/user_upload/')
            ->willReturn($parentFolder);

        $result = $this->subject->getExpiredFolders(86400, ['1:/user_upload/']);

        self::assertCount(1, $result);
        self::assertSame($validFolder, $result[0]);
    }
}
