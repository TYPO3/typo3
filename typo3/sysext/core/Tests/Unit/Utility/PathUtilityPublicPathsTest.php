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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for public URL transformation methods
 */
class PathUtilityPublicPathsTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    protected array $serverBackup;

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        parent::tearDown();
    }

    /**
     * Sets up Environment to simulate a frontend web request
     *
     * @param string $publicDir
     * @param string $subDirectory
     */
    protected static function simulateWebRequest(string $publicDir, string $subDirectory = '/'): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = $subDirectory . 'index.php';

        Environment::initialize(
            Environment::getContext(),
            false,
            true,
            Environment::getProjectPath(),
            $publicDir,
            Environment::getVarPath(),
            Environment::getConfigPath(),
            $publicDir . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
    }

    protected static function simulateWebRequestInComposerMode(): void
    {
        self::simulateWebRequest(Environment::getPublicPath() . '/typo3temp');
    }

    protected static function simulateWebRequestInComposerModeInSubDirectory(string $subDirectory = '/subDir/'): void
    {
        self::simulateWebRequest(Environment::getPublicPath() . '/typo3temp', $subDirectory);
    }

    protected static function simulateTraditionalWebRequest(): void
    {
        self::simulateWebRequest(Environment::getPublicPath());
    }

    protected static function simulateTraditionalWebRequestInSubDirectory(string $subDirectory = '/subDir/'): void
    {
        self::simulateWebRequest(Environment::getPublicPath(), $subDirectory);
    }

    /**
     * @test
     */
    public function tryingToResolveNonExtensionResourcesThrowsException(): void
    {
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionCode(1630089406);
        PathUtility::getPublicResourceWebPath('typo3/sysext/core/Resources/Public/Icons/Extension.svg');
    }

    /**
     * @test
     */
    public function tryingToResolvePrivateResourcesFromComposerPackagesThrowsException(): void
    {
        self::simulateWebRequestInComposerMode();
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionCode(1635268969);
        PathUtility::getPublicResourceWebPath('EXT:core/Resources/Private/Font/nimbus.ttf');
    }

    public static function getPublicResourceWebPathResolvesUrlsCorrectlyDataProvider(): array
    {
        return [
            'public assets are resolved to absolute url' => [
                'EXT:core/Resources/Public/Icons/Extension.svg',
                '/typo3/sysext/core/Resources/Public/Icons/Extension.svg',
                fn () => self::simulateTraditionalWebRequest(),
            ],
            'public assets are resolved to relative url on cli' => [
                'EXT:core/Resources/Public/Icons/Extension.svg',
                'typo3/sysext/core/Resources/Public/Icons/Extension.svg',
                fn () => true,
            ],
            'public assets are resolved to absolute url with sub directory prefixed' => [
                'EXT:core/Resources/Public/Icons/Extension.svg',
                '/cms/typo3/sysext/core/Resources/Public/Icons/Extension.svg',
                fn () => self::simulateTraditionalWebRequestInSubDirectory('/cms/'),
            ],
            'public assets are resolved to absolute url of published assets in Composer mode' => [
                'EXT:core/Resources/Public/Icons/Extension.svg',
                '/_assets/d25de869aebcd01495d2fe67ad5b0e25/Icons/Extension.svg',
                fn () => self::simulateWebRequestInComposerMode(),
            ],
            'public assets are resolved to absolute url of published assets in Composer mode with sub directory prefixed' => [
                'EXT:core/Resources/Public/Icons/Extension.svg',
                '/cms/_assets/d25de869aebcd01495d2fe67ad5b0e25/Icons/Extension.svg',
                fn () => self::simulateWebRequestInComposerModeInSubDirectory('/cms/'),
            ],
        ];
    }

    /**
     * @param string $pathReference
     * @param string $expectedUrl
     * @param callable $setup
     * @throws InvalidFileException
     * @dataProvider getPublicResourceWebPathResolvesUrlsCorrectlyDataProvider
     * @test
     */
    public function getPublicResourceWebPathResolvesUrlsCorrectly(string $pathReference, string $expectedUrl, callable $setup): void
    {
        $setup();
        self::assertSame(
            $expectedUrl,
            PathUtility::getPublicResourceWebPath($pathReference)
        );
    }
}
