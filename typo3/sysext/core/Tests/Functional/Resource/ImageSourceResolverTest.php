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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ImageSourceResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ImageSourceResolverTest extends FunctionalTestCase
{
    private const array FILES = [
        'typo3temp/assets/outside-storages/typo3-logo.png',
        'typo3temp/assets/.hidden/typo3-logo.png',
        'uploads/pics/typo3-logo.png',
        'outside-storages/typo3-logo.png',
        'fileadmin/typo3-logo.png',
    ];
    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::FILES as $file) {
            $absolutePath = Environment::getPublicPath() . '/' . $file;
            GeneralUtility::mkdir_deep(dirname($absolutePath));
            copy(__DIR__ . '/../Fixtures/Extensions/test_system_resources/Resources/Public/Images/typo3-logo.png', $absolutePath);
        }
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir(Environment::getPublicPath() . '/typo3temp/assets/outside-storages', true);
        GeneralUtility::rmdir(Environment::getPublicPath() . '/typo3temp/assets/.hidden', true);
        GeneralUtility::rmdir(Environment::getPublicPath() . '/uploads', true);
        GeneralUtility::rmdir(Environment::getPublicPath() . '/outside-storages', true);
        @unlink(Environment::getPublicPath() . '/fileadmin/typo3-logo.png');
        parent::tearDown();
    }

    public static function publicFilesOutsideOfFileStoragesAreResolvedDataProvider(): iterable
    {
        yield 'below typo3temp/assets/' => ['typo3temp/assets/outside-storages/typo3-logo.png'];
        yield 'below uploads/' => ['uploads/pics/typo3-logo.png'];
        yield 'below uploads/ with leading slash' => ['/uploads/pics/typo3-logo.png'];
    }

    #[DataProvider('publicFilesOutsideOfFileStoragesAreResolvedDataProvider')]
    #[Test]
    public function publicFilesOutsideOfFileStoragesAreResolved(string $path): void
    {
        $resource = $this->get(ImageSourceResolver::class)->resolvePublicFileOutsideOfFileStorages($path);

        self::assertNotNull($resource);
        self::assertSame('typo3-logo.png', $resource->getName());
    }

    public static function pathsAreNotResolvedDataProvider(): iterable
    {
        yield 'file in a directory not meant for files outside of storages' => ['outside-storages/typo3-logo.png'];
        yield 'file in a hidden directory' => ['typo3temp/assets/.hidden/typo3-logo.png'];
        yield 'back path out of an allowed directory' => ['typo3temp/assets/../../outside-storages/typo3-logo.png'];
        yield 'file within a file storage' => ['fileadmin/typo3-logo.png'];
        yield 'not existing file' => ['typo3temp/assets/outside-storages/missing.png'];
        yield 'directory' => ['typo3temp/assets/outside-storages/'];
    }

    #[DataProvider('pathsAreNotResolvedDataProvider')]
    #[Test]
    public function pathsAreNotResolved(string $path): void
    {
        self::assertNull($this->get(ImageSourceResolver::class)->resolvePublicFileOutsideOfFileStorages($path));
    }
}
