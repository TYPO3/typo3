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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\TextExtraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Resource\TextExtraction\LocalFileAccess;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LocalFileAccessTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function originalFileOfLocalStorageSurvivesIfItsNameDiffersFromTheIdentifier(): void
    {
        $path = Environment::getPublicPath() . '/fileadmin/user_upload/data.txt';
        GeneralUtility::mkdir_deep(dirname($path));
        file_put_contents($path, 'quokka');
        $file = new File(
            ['uid' => 1, 'identifier' => '/user_upload/data.txt', 'name' => 'Data.txt', 'mime_type' => 'text/plain'],
            $this->get(StorageRepository::class)->findByUid(1)
        );

        $content = LocalFileAccess::withLocalFile($file, static fn(string $localFilePath): string => (string)file_get_contents($localFilePath));

        self::assertSame('quokka', $content);
        self::assertFileExists($path);
    }

    #[Test]
    public function systemResourceIsProvidedAsTemporaryCopyWhichIsRemovedAfterwards(): void
    {
        $resource = self::createStub(SystemResourceInterface::class);
        $resource->method('getContents')->willReturn('quokka');
        $resource->method('getExtension')->willReturn('txt');
        $usedPath = '';

        $content = LocalFileAccess::withLocalFile($resource, static function (string $localFilePath) use (&$usedPath): string {
            $usedPath = $localFilePath;
            return (string)file_get_contents($localFilePath);
        });

        self::assertSame('quokka', $content);
        self::assertStringStartsWith(Environment::getVarPath() . '/transient/', $usedPath);
        self::assertStringEndsWith('.txt', $usedPath);
        self::assertFileDoesNotExist($usedPath);
    }

    #[Test]
    public function originalFileOfStorageBelowVarFolderSurvives(): void
    {
        $path = Environment::getVarPath() . '/private-storage/data.txt';
        GeneralUtility::mkdir_deep(dirname($path));
        file_put_contents($path, 'quokka');

        LocalFileAccess::withLocalFile($this->createFileProvidingLocalPath($path), static fn(string $localFilePath): string => $localFilePath);

        self::assertFileExists($path);
    }

    #[Test]
    public function temporaryCopyOfRemoteStorageIsRemoved(): void
    {
        $path = GeneralUtility::tempnam('fal-tempfile-', '.txt');
        file_put_contents($path, 'quokka');

        $content = LocalFileAccess::withLocalFile($this->createFileProvidingLocalPath($path), static fn(string $localFilePath): string => (string)file_get_contents($localFilePath));

        self::assertSame('quokka', $content);
        self::assertFileDoesNotExist($path);
    }

    private function createFileProvidingLocalPath(string $path): FileInterface
    {
        $file = self::createStub(FileInterface::class);
        $file->method('getForLocalProcessing')->willReturn($path);
        return $file;
    }
}
