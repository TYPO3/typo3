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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\EnableFileService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EnableFileServiceTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $environmentBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->environmentBackup = [
            'context' => Environment::getContext(),
            'cli' => Environment::isCli(),
            'composerMode' => Environment::isComposerMode(),
            'projectPath' => Environment::getProjectPath(),
            'publicPath' => Environment::getPublicPath(),
            'varPath' => Environment::getVarPath(),
            'configPath' => Environment::getConfigPath(),
            'currentScript' => Environment::getCurrentScript(),
            'os' => Environment::isWindows() ? 'WINDOWS' : 'UNIX',
        ];
    }

    public function tearDown(): void
    {
        $publicPath = Environment::getPublicPath();
        $projectPath = Environment::getProjectPath();

        @rmdir($publicPath . '/FIRST_INSTALL2Folder');
        @unlink($publicPath . '/FIRST_INSTALL');
        @unlink($publicPath . '/FIRST_INStall');
        @unlink($publicPath . '/FIRST_INSTALL.txt');
        @unlink($publicPath . '/foo');
        @unlink($publicPath . '/bar');
        @unlink($publicPath . '/ddd.txt');
        @unlink($publicPath . '/somethingelse');
        @unlink($publicPath . '/dadadaFIRST_INStall');
        if ($publicPath !== $projectPath) {
            @unlink($projectPath . '/FIRST_INSTALL');
            @unlink($projectPath . '/FIRST_INStall');
            @unlink($projectPath . '/FIRST_INSTALL.txt');
            @unlink($projectPath . '/somethingelse');
            @rmdir($projectPath . '/FIRST_INSTALL2Folder');
            @rmdir($publicPath);
        }

        Environment::initialize(...$this->environmentBackup);

        parent::tearDown();
    }

    private function prepareEnvironment(bool $composerMode): void
    {
        if ($composerMode === true) {
            Environment::initialize(
                Environment::getContext(),
                Environment::isCli(),
                true,
                Environment::getProjectPath(),
                Environment::getProjectPath() . '/public',
                Environment::getVarPath(),
                Environment::getConfigPath(),
                Environment::getCurrentScript(),
                Environment::isWindows() ? 'WINDOWS' : 'UNIX',
            );
            GeneralUtility::mkdir_deep(Environment::getPublicPath());
        } else {
            Environment::initialize(
                Environment::getContext(),
                Environment::isCli(),
                false,
                Environment::getProjectPath(),
                Environment::getProjectPath(),
                Environment::getVarPath(),
                Environment::getConfigPath(),
                Environment::getCurrentScript(),
                Environment::isWindows() ? 'WINDOWS' : 'UNIX',
            );
        }
    }

    #[Test]
    public function getFirstInstallFilePathsFindsValidFiles(): void
    {
        $this->prepareEnvironment(false);
        $projectPath = Environment::getProjectPath();
        $publicPath = Environment::getPublicPath();
        self::assertSame($projectPath, $publicPath);
        self::assertFalse(Environment::isComposerMode());
        mkdir($publicPath . '/FIRST_INSTALL2Folder');
        file_put_contents($publicPath . '/FIRST_INSTALL', '');
        file_put_contents($publicPath . '/FIRST_INStall', '');
        file_put_contents($publicPath . '/FIRST_INSTALL.txt', 'with content');
        file_put_contents($publicPath . '/somethingelse', '');
        file_put_contents($publicPath . '/dadadaFIRST_INStall', '');
        $expected = [
            $publicPath . '/FIRST_INSTALL',
            $publicPath . '/FIRST_INStall',
            $publicPath . '/FIRST_INSTALL.txt',
        ];
        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        self::assertEquals([], array_diff($expected, $subject->_call('getFirstInstallFilePaths')));
    }

    #[Test]
    public function getFirstInstallFilePathsFindsValidFilesInProjectPath(): void
    {
        $this->prepareEnvironment(true);
        $projectPath = Environment::getProjectPath();
        $publicPath = Environment::getPublicPath();
        self::assertNotSame($projectPath, $publicPath);
        self::assertTrue(Environment::isComposerMode());

        file_put_contents($projectPath . '/FIRST_INSTALL', '');
        file_put_contents($projectPath . '/FIRST_INStall', '');
        file_put_contents($projectPath . '/FIRST_INSTALL.txt', 'with content');
        file_put_contents($projectPath . '/somethingelse', '');

        $expected = [
            $projectPath . '/FIRST_INSTALL',
            $projectPath . '/FIRST_INStall',
            $projectPath . '/FIRST_INSTALL.txt',
        ];
        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        $result = $subject->_call('getFirstInstallFilePaths');

        // Check that all expected files are found
        self::assertEquals([], array_diff($expected, $result));
    }

    #[Test]
    public function getFirstInstallFilePathsFindsValidFilesInBothPaths(): void
    {
        $this->prepareEnvironment(true);
        $projectPath = Environment::getProjectPath();
        $publicPath = Environment::getPublicPath();
        self::assertNotSame($projectPath, $publicPath);
        self::assertTrue(Environment::isComposerMode());

        // Create files in public path
        file_put_contents($publicPath . '/FIRST_INSTALL', '');
        file_put_contents($publicPath . '/FIRST_INStall', '');

        // Create files in project path
        file_put_contents($projectPath . '/FIRST_INSTALL.txt', 'with content');

        $expected = [
            $publicPath . '/FIRST_INSTALL',
            $publicPath . '/FIRST_INStall',
            $projectPath . '/FIRST_INSTALL.txt',
        ];
        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        $result = $subject->_call('getFirstInstallFilePaths');

        // Check that all expected files are found
        self::assertEquals([], array_diff($expected, $result));
    }

    #[Test]
    public function getFirstInstallFilePathsReturnsUniqueFilesWhenSameFileInBothPaths(): void
    {
        $this->prepareEnvironment(true);
        $projectPath = Environment::getProjectPath();
        $publicPath = Environment::getPublicPath();
        self::assertNotSame($projectPath, $publicPath);
        self::assertTrue(Environment::isComposerMode());

        // Create same file in both paths
        file_put_contents($publicPath . '/FIRST_INSTALL', '');
        file_put_contents($projectPath . '/FIRST_INSTALL', '');

        $expected = [
            $publicPath . '/FIRST_INSTALL',
            $projectPath . '/FIRST_INSTALL',
        ];
        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        $result = $subject->_call('getFirstInstallFilePaths');

        self::assertCount(2, $result);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getFirstInstallFilePathsReturnsEmptyArrayWithOnlyInvalidFiles(): void
    {
        $this->prepareEnvironment(false);
        $projectPath = Environment::getProjectPath();
        $publicPath = Environment::getPublicPath();
        self::assertSame($projectPath, $publicPath);
        self::assertFalse(Environment::isComposerMode());
        mkdir($publicPath . '/FIRST_INSTALL2Folder');
        file_put_contents($publicPath . '/foo', '');
        file_put_contents($publicPath . '/bar', '');
        file_put_contents($publicPath . '/ddd.txt', 'with content');
        file_put_contents($publicPath . '/somethingelse', '');
        file_put_contents($publicPath . '/dadadaFIRST_INStall', '');
        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        self::assertEquals([], array_diff([], $subject->_call('getFirstInstallFilePaths')));
    }

    #[Test]
    public function removeFirstInstallFileRemovesValidFiles(): void
    {
        $this->prepareEnvironment(false);
        $projectPath = Environment::getProjectPath();
        $publicPath = Environment::getPublicPath();
        self::assertSame($projectPath, $publicPath);
        self::assertFalse(Environment::isComposerMode());
        mkdir($publicPath . '/FIRST_INSTALL2Folder');
        file_put_contents($publicPath . '/FIRST_INSTALL', '');
        file_put_contents($publicPath . '/FIRST_INStall', '');
        file_put_contents($publicPath . '/FIRST_INSTALL.txt', 'with content');
        file_put_contents($publicPath . '/somethingelse', '');
        file_put_contents($publicPath . '/dadadaFIRST_INStall', '');
        $expected = scandir($publicPath);
        unset($expected[2], $expected[3], $expected[5]);
        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        $subject->_call('removeFirstInstallFile');
        self::assertEquals(array_values($expected), array_values(scandir($publicPath)));
    }

    #[Test]
    public function removeFirstInstallFileRemovesValidFilesFromProjectPath(): void
    {
        $this->prepareEnvironment(true);
        $projectPath = Environment::getProjectPath();
        $publicPath = Environment::getPublicPath();
        self::assertNotSame($projectPath, $publicPath);
        self::assertTrue(Environment::isComposerMode());

        file_put_contents($projectPath . '/FIRST_INSTALL', '');
        file_put_contents($projectPath . '/FIRST_INStall', '');
        file_put_contents($projectPath . '/FIRST_INSTALL.txt', 'with content');

        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        $subject->_call('removeFirstInstallFile');

        // Verify files were removed from project path
        self::assertFileDoesNotExist($projectPath . '/FIRST_INSTALL');
        self::assertFileDoesNotExist($projectPath . '/FIRST_INStall');
        self::assertFileDoesNotExist($projectPath . '/FIRST_INSTALL.txt');
    }

    #[Test]
    public function removeFirstInstallFileRemovesValidFilesFromBothPaths(): void
    {
        $this->prepareEnvironment(true);
        $projectPath = Environment::getProjectPath();
        $publicPath = Environment::getPublicPath();
        self::assertNotSame($projectPath, $publicPath);
        self::assertTrue(Environment::isComposerMode());

        // Create files in both paths
        file_put_contents($publicPath . '/FIRST_INSTALL', '');
        file_put_contents($projectPath . '/FIRST_INSTALL', '');
        file_put_contents($publicPath . '/FIRST_INStall', '');

        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        $subject->_call('removeFirstInstallFile');

        // Verify files were removed from both paths
        self::assertFileDoesNotExist($publicPath . '/FIRST_INSTALL');
        self::assertFileDoesNotExist($projectPath . '/FIRST_INSTALL');
        self::assertFileDoesNotExist($publicPath . '/FIRST_INStall');
    }

    #[Test]
    public function removeFirstInstallFileRemovesNoFileIfThereAreNoValidFiles(): void
    {
        $this->prepareEnvironment(false);
        $projectPath = Environment::getProjectPath();
        $publicPath = Environment::getPublicPath();
        self::assertSame($projectPath, $publicPath);
        self::assertFalse(Environment::isComposerMode());
        mkdir($publicPath . '/FIRST_INSTALL2Folder');
        file_put_contents($publicPath . '/foo', '');
        file_put_contents($publicPath . '/bar', '');
        file_put_contents($publicPath . '/ddd.txt', 'with content');
        file_put_contents($publicPath . '/somethingelse', '');
        file_put_contents($publicPath . '/dadadaFIRST_INStall', '');
        $expected = scandir($publicPath);
        $subject = $this->getAccessibleMock(EnableFileService::class, null);
        $subject->_call('removeFirstInstallFile');
        self::assertEquals(array_values($expected), array_values(scandir($publicPath)));
    }

    #[Test]
    public function removeInstallToolEnableFileRemovesAllAvailableFiles(): void
    {
        $defaultLocation = Environment::getVarPath() . '/transient/' . EnableFileService::INSTALL_TOOL_ENABLE_FILE_PATH;
        $permanentLocation = Environment::getConfigPath() . '/' . EnableFileService::INSTALL_TOOL_ENABLE_FILE_PATH;
        $legacyLocation = Environment::getLegacyConfigPath() . EnableFileService::INSTALL_TOOL_ENABLE_FILE_PATH;
        @mkdir(dirname($defaultLocation));
        @mkdir(dirname($permanentLocation));
        @mkdir(dirname($legacyLocation));
        file_put_contents($defaultLocation, 'abc');
        file_put_contents($permanentLocation, 'abc');
        file_put_contents($legacyLocation, 'abc');
        $subject = new EnableFileService();
        $result = $subject::removeInstallToolEnableFile();
        self::assertTrue($result);
        self::assertFileDoesNotExist($defaultLocation);
        self::assertFileDoesNotExist($permanentLocation);
        self::assertFileDoesNotExist($legacyLocation);
    }
}
