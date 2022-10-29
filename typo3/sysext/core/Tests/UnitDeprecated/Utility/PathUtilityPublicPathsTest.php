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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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

    protected static function simulateTraditionalWebRequest(): void
    {
        self::simulateWebRequest(Environment::getPublicPath());
    }

    protected static function simulateTraditionalWebRequestInSubDirectory(string $subDirectory = '/subDir/'): void
    {
        self::simulateWebRequest(Environment::getPublicPath(), $subDirectory);
    }

    public static function getPublicResourceWebPathResolvesUrlsCorrectlyDataProvider(): array
    {
        return [
            'private assets are resolved to absolute url' => [
                'EXT:core/Resources/Private/Font/nimbus.ttf',
                '/typo3/sysext/core/Resources/Private/Font/nimbus.ttf',
                fn () => self::simulateTraditionalWebRequest(),
            ],
            'private assets are resolved to absolute url with sub directory prefixed' => [
                'EXT:core/Resources/Private/Font/nimbus.ttf',
                '/cms/typo3/sysext/core/Resources/Private/Font/nimbus.ttf',
                fn () => self::simulateTraditionalWebRequestInSubDirectory('/cms/'),
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
