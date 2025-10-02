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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FilePathSanitizerTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    /**
     * Sets up Environment to simulate Composer mode and a frontend web request
     */
    protected function simulateWebRequestInComposerMode(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $fakePublicDir = Environment::getProjectPath() . '/typo3temp';

        Environment::initialize(
            Environment::getContext(),
            false,
            true,
            Environment::getProjectPath(),
            $fakePublicDir,
            Environment::getVarPath(),
            Environment::getConfigPath(),
            $fakePublicDir . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        if (!is_file($fakePublicDir . '/index.php')) {
            file_put_contents($fakePublicDir . '/index.php', '<?php');
        }
        $this->testFilesToDelete[] = $fakePublicDir . '/index.php';
    }

    #[Test]
    public function tryingToResolvePrivateResourcesFromComposerPackagesThrowsException(): void
    {
        $this->simulateWebRequestInComposerMode();
        $this->expectException(InvalidFileException::class);
        $subject = new FilePathSanitizer();
        $subject->sanitize('EXT:frontend/Resources/Private/Templates/MainPage.html');
    }

    #[Test]
    public function settingSecondArgumentToFalseIsNotAllowed(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $subject = new FilePathSanitizer();
        $subject->sanitize('anything', false);
    }

    public static function publicAssetsInComposerModeResolvedCorrectlyDataProvider(): array
    {
        return [
            'insecure URL returned as is' => [
                'http://example.com',
                'http://example.com',
            ],
            'secure URL returned as is' => [
                'http://example.com',
                'http://example.com',
            ],
            'insecure URL returned as is, regardless of second argument' => [
                'http://example.com',
                'http://example.com',
                true,
            ],
            'secure URL returned as is, regardless of second argument' => [
                'http://example.com',
                'http://example.com',
                true,
            ],
            'relative input within existing public path' => [
                'index.php',
                'index.php',
            ],
            'spaces are trimmed from input' => [
                '  index.php  ',
                'index.php',
            ],
            'extension paths are resolved as is, when second argument is true' => [
                'EXT:frontend/Resources/Private/Templates/MainPage.html',
                'EXT:frontend/Resources/Private/Templates/MainPage.html',
                true,
            ],
            'public extension assets resolved to published assets path' => [
                'EXT:frontend/Resources/Public/Icons/Extension.svg',
                '_assets/60fb7e6e5897b3717bf625a31c949978/Icons/Extension.svg',
            ],
        ];
    }

    #[DataProvider('publicAssetsInComposerModeResolvedCorrectlyDataProvider')]
    #[Test]
    public function publicAssetsInComposerModeResolvedCorrectly(string $givenPathOrUrl, string $expectedPathOrUrl, ?bool $allowExtensionPath = null): void
    {
        $this->simulateWebRequestInComposerMode();
        $subject = new FilePathSanitizer();
        self::assertSame($expectedPathOrUrl, $subject->sanitize($givenPathOrUrl, $allowExtensionPath));
    }

    public static function sanitizeCorrectlyResolvesPathsAndUrlsDataProvider(): array
    {
        return [
            'insecure URL returned as is' => [
                'http://example.com',
                'http://example.com',
            ],
            'secure URL returned as is' => [
                'http://example.com',
                'http://example.com',
            ],
            'insecure URL returned as is, regardless of second argument' => [
                'http://example.com',
                'http://example.com',
                true,
            ],
            'secure URL returned as is, regardless of second argument' => [
                'http://example.com',
                'http://example.com',
                true,
            ],
            'relative input within existing public path' => [
                'typo3/install.php',
                'typo3/install.php',
            ],
            'spaces are trimmed from input' => [
                '  typo3/install.php  ',
                'typo3/install.php',
            ],
            'extension paths are resolved as is, when second argument is true' => [
                'EXT:frontend/Resources/Private/Templates/MainPage.html',
                'EXT:frontend/Resources/Private/Templates/MainPage.html',
                true,
            ],
            'absolute paths are made relative, even when second argument is true' => [
                Environment::getFrameworkBasePath() . '/frontend/Resources/Private/Templates/MainPage.html',
                'typo3/sysext/frontend/Resources/Private/Templates/MainPage.html',
                true,
            ],
        ];
    }

    #[DataProvider('sanitizeCorrectlyResolvesPathsAndUrlsDataProvider')]
    #[Test]
    public function sanitizeCorrectlyResolvesPathsAndUrls(string $givenPathOrUrl, string $expectedPathOrUrl, ?bool $allowExtensionPath = null): void
    {
        $subject = new FilePathSanitizer();
        self::assertSame($expectedPathOrUrl, $subject->sanitize($givenPathOrUrl, $allowExtensionPath));
    }

    #[Test]
    public function sanitizeFailsIfDirectoryGiven(): void
    {
        $this->expectException(FileDoesNotExistException::class);
        $subject = new FilePathSanitizer();
        $subject->sanitize(__DIR__);
    }

    #[Test]
    public function sanitizeThrowsExceptionWithFileNameContainingOnlySpaces(): void
    {
        $this->expectException(InvalidFileNameException::class);
        (new FilePathSanitizer())->sanitize('  ');
    }

    #[Test]
    public function sanitizeThrowsExceptionWithInvalidFileName(): void
    {
        $this->expectException(InvalidPathException::class);
        (new FilePathSanitizer())->sanitize('something/../else');
    }

    #[Test]
    #[IgnoreDeprecations]
    public function sanitizeCorrectlyResolvesPathsForLegacySystemsEvenForPrivateResources(): void
    {
        $subject = new FilePathSanitizer();
        self::assertSame('typo3/sysext/frontend/Resources/Private/Templates/MainPage.html', $subject->sanitize('EXT:frontend/Resources/Private/Templates/MainPage.html'));
    }
}
