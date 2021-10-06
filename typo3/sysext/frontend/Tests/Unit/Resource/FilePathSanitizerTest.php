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

namespace TYPO3\CMS\Frontend\Tests\Unit\Resource;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Frontend\Resource\FilePathSanitizer
 */
class FilePathSanitizerTest extends UnitTestCase
{
    protected $backupEnvironment = true;

    /**
     * @test
     */
    public function sanitizeReturnsUrlCorrectly(): void
    {
        $subject = new FilePathSanitizer();
        self::assertSame('http://example.com', $subject->sanitize('http://example.com'));
        self::assertSame('https://example.com', $subject->sanitize('https://example.com'));
    }

    /**
     * @test
     */
    public function tryingToResolvePrivateResourcesFromComposerPackagesThrowsException(): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            true,
            Environment::getProjectPath() . '/public',
            Environment::getPublicPath() . '/public',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $this->expectException(InvalidFileException::class);
        $subject = new FilePathSanitizer();
        $subject->sanitize('EXT:frontend/Resources/Private/Templates/MainPage.html');
    }

    /**
     * @test
     */
    public function publicAssetsFromComposerPackgesResolveToAssetUrl(): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            true,
            Environment::getProjectPath() . '/public',
            Environment::getPublicPath() . '/public',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $subject = new FilePathSanitizer();
        self::assertSame('_assets/60fb7e6e5897b3717bf625a31c949978/Icons/Extension.svg', $subject->sanitize('EXT:frontend/Resources/Public/Icons/Extension.svg'));
    }

    /**
     * @test
     */
    public function extensionPathsAreReturnedAsIsButAbsolutePathsAreStillMadeRelativeWhenExtensionPathsAreAllowedToBeReturned(): void
    {
        $subject = new FilePathSanitizer();
        self::assertSame('EXT:frontend/Resources/Private/Templates/MainPage.html', $subject->sanitize('EXT:frontend/Resources/Private/Templates/MainPage.html', true));
        self::assertSame('typo3/sysext/frontend/Resources/Private/Templates/MainPage.html', $subject->sanitize(Environment::getFrameworkBasePath() . '/frontend/Resources/Private/Templates/MainPage.html', true));
    }

    /**
     * @test
     */
    public function settingSecondArgumentToFalseIsNotAllowed(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $subject = new FilePathSanitizer();
        $subject->sanitize('anything', false);
    }

    public static function sanitizeReturnsRelativePathsDataProvider(): array
    {
        return [
            'relative input within existing public path' => [
                'typo3/index.php',
                'typo3/index.php',
            ],
            'legacy systems resolve private resources in public path' => [
                'EXT:frontend/Resources/Private/Templates/MainPage.html',
                'typo3/sysext/frontend/Resources/Private/Templates/MainPage.html',
            ],
        ];
    }

    /**
     * @param string $absolutePath
     * @param string $expectedRelativePath
     * @test
     * @dataProvider sanitizeReturnsRelativePathsDataProvider
     */
    public function sanitizeReturnsRelativePaths(string $absolutePath, string $expectedRelativePath): void
    {
        $subject = new FilePathSanitizer();
        self::assertSame($expectedRelativePath, $subject->sanitize($absolutePath));
    }

    /**
     * @test
     */
    public function sanitizeFailsIfDirectoryGiven(): void
    {
        $this->expectException(FileDoesNotExistException::class);
        $subject = new FilePathSanitizer();
        $subject->sanitize(__DIR__);
    }

    /**
     * @test
     */
    public function sanitizeThrowsExceptionWithInvalidFileName(): void
    {
        $this->expectException(InvalidFileNameException::class);
        self::assertNull((new FilePathSanitizer())->sanitize('  '));
        self::assertNull((new FilePathSanitizer())->sanitize('something/../else'));
    }
}
