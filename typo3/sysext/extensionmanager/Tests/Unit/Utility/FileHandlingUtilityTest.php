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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\Archive\ZipService;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extensionmanager\Utility\EmConfUtility;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileHandlingUtilityTest extends UnitTestCase
{
    /**
     * @var array List of created fake extensions
     */
    private array $fakedExtensions = [];

    private string $testRoot;

    protected function setUp(): void
    {
        // The unique sub directory keeps the files of other test cases, which use
        // the same root, out of the cleanup below.
        $this->testRoot = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('fileHandling_') . '/';
        GeneralUtility::mkdir_deep($this->testRoot);
        $this->testFilesToDelete[] = $this->testRoot;
        parent::setUp();
    }

    /**
     * Creates a fake extension inside typo3temp/. No configuration is created,
     * just the folder
     *
     * @return string The extension key
     */
    private function createFakeExtension(): string
    {
        $extKey = strtolower(StringUtility::getUniqueId('testing'));
        $absExtPath = $this->testRoot . 'ext-' . $extKey . '/';
        GeneralUtility::mkdir_deep($absExtPath);
        $this->fakedExtensions[$extKey] = [
            'packagePath' => $absExtPath,
        ];
        return $extKey;
    }

    #[Test]
    public function makeAndClearExtensionDirRemovesExtensionDirIfAlreadyExists(): void
    {
        $extKey = $this->createFakeExtension();
        $path = $this->fakedExtensions[$extKey]['packagePath'];
        $subject = $this->getAccessibleMock(FileHandlingUtility::class, ['removeDirectory', 'addDirectory', 'getExtensionDir'], [], '', false);
        $subject->expects($this->once())->method('removeDirectory')->with($path);
        $subject->method('getExtensionDir')->willReturn($path);
        $subject->_call('makeAndClearExtensionDir', $extKey);
    }

    #[Test]
    public function makeAndClearExtensionDirAddsDir(): void
    {
        $extKey = $this->createFakeExtension();
        $subject = $this->getAccessibleMock(FileHandlingUtility::class, ['removeDirectory', 'addDirectory', 'getExtensionDir'], [], '', false);
        $subject->expects($this->once())->method('addDirectory')->with($this->testRoot . 'ext-' . $extKey . '/');
        $subject->method('getExtensionDir')->willReturn($this->testRoot . 'ext-' . $extKey . '/');
        $subject->_call('makeAndClearExtensionDir', $extKey);
    }

    #[Test]
    public function addDirectoryAddsDirectory(): void
    {
        $extDirPath = $this->testRoot . StringUtility::getUniqueId('test-extensions-');
        $subject = $this->getAccessibleMock(FileHandlingUtility::class, null, [], '', false);
        $subject->_call('addDirectory', $extDirPath);
        self::assertDirectoryExists($extDirPath);
    }

    #[Test]
    public function removeDirectoryRemovesDirectory(): void
    {
        $extDirPath = $this->testRoot . StringUtility::getUniqueId('test-extensions-');
        @mkdir($extDirPath);
        $subject = $this->getAccessibleMock(FileHandlingUtility::class, null, [], '', false);
        $subject->removeDirectory($extDirPath);
        self::assertDirectoryDoesNotExist($extDirPath);
    }

    #[Test]
    public function removeDirectoryRemovesSymlink(): void
    {
        $absoluteSymlinkPath = $this->testRoot . StringUtility::getUniqueId('test_symlink_');
        $absoluteFilePath = $this->testRoot . StringUtility::getUniqueId('test_file_');
        touch($absoluteFilePath);
        symlink($absoluteFilePath, $absoluteSymlinkPath);
        $subject = new FileHandlingUtility(
            $this->createMock(PackageManager::class),
            $this->createMock(EmConfUtility::class),
            $this->createMock(OpcodeCacheService::class),
            $this->createMock(ZipService::class),
            $this->createMock(LanguageServiceFactory::class)
        );
        $subject->removeDirectory($absoluteSymlinkPath);
        self::assertFalse(is_link($absoluteSymlinkPath));
    }

    #[Test]
    public function removeDirectoryDoesNotRemoveContentOfSymlinkedTargetDirectory(): void
    {
        $absoluteSymlinkPath = $this->testRoot . StringUtility::getUniqueId('test_symlink_');
        $absoluteDirectoryPath = $this->testRoot . StringUtility::getUniqueId('test_dir_') . '/';
        $relativeFilePath = StringUtility::getUniqueId('test_file_');
        GeneralUtility::mkdir_deep($absoluteDirectoryPath);
        touch($absoluteDirectoryPath . $relativeFilePath);
        symlink($absoluteDirectoryPath, $absoluteSymlinkPath);
        $subject = new FileHandlingUtility(
            $this->createMock(PackageManager::class),
            $this->createMock(EmConfUtility::class),
            $this->createMock(OpcodeCacheService::class),
            $this->createMock(ZipService::class),
            $this->createMock(LanguageServiceFactory::class)
        );
        $subject->removeDirectory($absoluteSymlinkPath);
        self::assertTrue(is_file($absoluteDirectoryPath . $relativeFilePath));
    }

    #[Test]
    public function unpackExtensionFromExtensionDataArrayCreatesTheExtensionDirectory(): void
    {
        $extensionKey = 'test';
        $subject = $this->getAccessibleMock(
            FileHandlingUtility::class,
            [
                'makeAndClearExtensionDir',
                'writeEmConfToFile',
                'enrichComposerJsonWithComposerCapableFields',
                'extractDirectoriesFromExtensionData',
                'createDirectoriesForExtensionFiles',
                'writeExtensionFiles',
                'reloadPackageInformation',
            ],
            [],
            '',
            false
        );
        $subject->expects($this->once())->method('extractDirectoriesFromExtensionData')->willReturn([]);
        $subject->expects($this->once())->method('makeAndClearExtensionDir')->with($extensionKey)->willReturn('my_path');
        $subject->unpackExtensionFromExtensionDataArray($extensionKey, [], '1.0.0');
    }

    #[Test]
    public function unpackExtensionFromExtensionDataArrayStripsDirectoriesFromFilesArray(): void
    {
        $extensionData = [
            'extKey' => 'test',
            'FILES' => [
                'ChangeLog' => [
                    'name' => 'ChangeLog',
                    'size' => 4559,
                    'mtime' => 1219448527,
                    'is_executable' => false,
                    'content' => 'some content to write',
                ],
                'doc/' => [
                    'name' => 'doc/',
                    'size' => 0,
                    'mtime' => 1219448527,
                    'is_executable' => false,
                    'content' => '',
                ],
                'doc/ChangeLog' => [
                    'name' => 'ChangeLog',
                    'size' => 4559,
                    'mtime' => 1219448527,
                    'is_executable' => false,
                    'content' => 'some content to write',
                ],
            ],
        ];
        $cleanedFiles = [
            'ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write',
            ],
            'doc/ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write',
            ],
        ];
        $directories = [
            'doc/',
            'mod/doc/',
        ];

        $subject = $this->getAccessibleMock(
            FileHandlingUtility::class,
            [
                'makeAndClearExtensionDir',
                'writeEmConfToFile',
                'enrichComposerJsonWithComposerCapableFields',
                'extractDirectoriesFromExtensionData',
                'createDirectoriesForExtensionFiles',
                'writeExtensionFiles',
                'reloadPackageInformation',
            ],
            [],
            '',
            false
        );
        $subject->expects($this->once())->method('extractDirectoriesFromExtensionData')->willReturn($directories);
        $subject->expects($this->once())->method('createDirectoriesForExtensionFiles')->with($directories);
        $subject->expects($this->once())->method('makeAndClearExtensionDir')->with($extensionData['extKey'])->willReturn('my_path');
        $subject->expects($this->once())->method('writeExtensionFiles')->with($cleanedFiles);
        $subject->expects($this->once())->method('reloadPackageInformation')->with('test');
        $subject->unpackExtensionFromExtensionDataArray('test', $extensionData, '1.0.0');
    }

    #[Test]
    public function writeExtensionFilesWritesFiles(): void
    {
        $files = [
            'ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write',
            ],
            'README' => [
                'name' => 'README',
                'size' => 4566,
                'mtime' => 1219448533,
                'is_executable' => false,
                'content' => 'FEEL FREE TO ADD SOME DOCUMENTATION HERE',
            ],
        ];
        $rootPath = $this->fakedExtensions[$this->createFakeExtension()]['packagePath'];
        $subject = $this->getAccessibleMock(FileHandlingUtility::class, ['makeAndClearExtensionDir'], [], '', false);
        $subject->_call('writeExtensionFiles', $files, $rootPath);
        self::assertFileExists($rootPath . 'ChangeLog');
    }

    #[Test]
    public function extractDirectoriesFromExtensionDataExtractsDirectories(): void
    {
        $files = [
            'ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write',
            ],
            'doc/' => [
                'name' => 'doc/',
                'size' => 0,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => '',
            ],
            'doc/ChangeLog' => [
                'name' => 'ChangeLog',
                'size' => 4559,
                'mtime' => 1219448527,
                'is_executable' => false,
                'content' => 'some content to write',
            ],
            'doc/README' => [
                'name' => 'README',
                'size' => 4566,
                'mtime' => 1219448533,
                'is_executable' => false,
                'content' => 'FEEL FREE TO ADD SOME DOCUMENTATION HERE',
            ],
            'mod/doc/README' => [
                'name' => 'README',
                'size' => 4566,
                'mtime' => 1219448533,
                'is_executable' => false,
                'content' => 'FEEL FREE TO ADD SOME DOCUMENTATION HERE',
            ],
        ];
        $subject = $this->getAccessibleMock(FileHandlingUtility::class, ['makeAndClearExtensionDir'], [], '', false);
        $extractedDirectories = $subject->_call('extractDirectoriesFromExtensionData', $files);
        $expected = [
            'doc/',
            'mod/doc/',
        ];
        self::assertSame($expected, array_values($extractedDirectories));
    }

    #[Test]
    public function createDirectoriesForExtensionFilesCreatesDirectories(): void
    {
        $rootPath = $this->fakedExtensions[$this->createFakeExtension()]['packagePath'];
        $directories = [
            'doc/',
            'mod/doc/',
        ];
        $subject = $this->getAccessibleMock(FileHandlingUtility::class, ['makeAndClearExtensionDir'], [], '', false);
        self::assertDirectoryDoesNotExist($rootPath . 'doc/');
        self::assertDirectoryDoesNotExist($rootPath . 'mod/doc/');
        $subject->_call('createDirectoriesForExtensionFiles', $directories, $rootPath);
        self::assertDirectoryExists($rootPath . 'doc/');
        self::assertDirectoryExists($rootPath . 'mod/doc/');
    }

    #[Test]
    public function enrichComposerJsonWithComposerCapableFieldsAddsVersionAndProvidesPackages(): void
    {
        $extKey = $this->createFakeExtension();
        $rootPath = $this->fakedExtensions[$extKey]['packagePath'];
        file_put_contents($rootPath . 'composer.json', json_encode([
            'name' => 'vendor/' . $extKey,
            'type' => 'typo3-cms-extension',
            'extra' => [
                'typo3/cms' => [
                    'extension-key' => $extKey,
                ],
            ],
        ]));
        file_put_contents($rootPath . 'ext_emconf.php', '<?php $EM_CONF[$_EXTKEY] = ["version" => "2.3.4"];');
        $emConfUtility = new EmConfUtility();
        $subject = $this->getAccessibleMock(
            FileHandlingUtility::class,
            null,
            [
                $this->createMock(PackageManager::class),
                $emConfUtility,
                $this->createMock(OpcodeCacheService::class),
                $this->createMock(ZipService::class),
                $this->createMock(LanguageServiceFactory::class),
            ]
        );
        $subject->_call('enrichComposerJsonWithComposerCapableFields', $extKey, $rootPath);
        $composerJson = json_decode(file_get_contents($rootPath . 'composer.json'), true);
        self::assertArrayNotHasKey('version', $composerJson);
        self::assertSame('2.3.4', $composerJson['extra']['typo3/cms']['version']);
        self::assertSame([], (array)$composerJson['extra']['typo3/cms']['Package']['providesPackages']);
    }

    #[Test]
    public function enrichComposerJsonWithComposerCapableFieldsDoesNotOverwriteExistingValues(): void
    {
        $extKey = $this->createFakeExtension();
        $rootPath = $this->fakedExtensions[$extKey]['packagePath'];
        file_put_contents($rootPath . 'composer.json', json_encode([
            'name' => 'vendor/' . $extKey,
            'type' => 'typo3-cms-extension',
            'version' => '1.0.0',
            'extra' => [
                'typo3/cms' => [
                    'extension-key' => $extKey,
                    'Package' => [
                        'providesPackages' => ['some/package' => ''],
                    ],
                ],
            ],
        ]));
        file_put_contents($rootPath . 'ext_emconf.php', '<?php $EM_CONF[$_EXTKEY] = ["version" => "2.3.4"];');
        $emConfUtility = new EmConfUtility();
        $subject = $this->getAccessibleMock(
            FileHandlingUtility::class,
            null,
            [
                $this->createMock(PackageManager::class),
                $emConfUtility,
                $this->createMock(OpcodeCacheService::class),
                $this->createMock(ZipService::class),
                $this->createMock(LanguageServiceFactory::class),
            ]
        );
        $subject->_call('enrichComposerJsonWithComposerCapableFields', $extKey, $rootPath);
        $composerJson = json_decode(file_get_contents($rootPath . 'composer.json'), true);
        self::assertSame('1.0.0', $composerJson['version']);
        self::assertArrayNotHasKey('version', $composerJson['extra']['typo3/cms']);
        self::assertSame(['some/package' => ''], $composerJson['extra']['typo3/cms']['Package']['providesPackages']);
    }

    #[Test]
    public function enrichComposerJsonWithComposerCapableFieldsDoesNotOverwriteExistingExtraVersion(): void
    {
        $extKey = $this->createFakeExtension();
        $rootPath = $this->fakedExtensions[$extKey]['packagePath'];
        file_put_contents($rootPath . 'composer.json', json_encode([
            'name' => 'vendor/' . $extKey,
            'type' => 'typo3-cms-extension',
            'extra' => [
                'typo3/cms' => [
                    'extension-key' => $extKey,
                    'version' => '3.0.0',
                ],
            ],
        ]));
        file_put_contents($rootPath . 'ext_emconf.php', '<?php $EM_CONF[$_EXTKEY] = ["version" => "2.3.4"];');
        $subject = $this->getAccessibleMock(
            FileHandlingUtility::class,
            null,
            [
                $this->createMock(PackageManager::class),
                new EmConfUtility(),
                $this->createMock(OpcodeCacheService::class),
                $this->createMock(ZipService::class),
                $this->createMock(LanguageServiceFactory::class),
            ]
        );
        $subject->_call('enrichComposerJsonWithComposerCapableFields', $extKey, $rootPath);
        $composerJson = json_decode(file_get_contents($rootPath . 'composer.json'), true);
        self::assertArrayNotHasKey('version', $composerJson);
        self::assertSame('3.0.0', $composerJson['extra']['typo3/cms']['version']);
        self::assertSame([], (array)$composerJson['extra']['typo3/cms']['Package']['providesPackages']);
    }

    #[Test]
    public function enrichComposerJsonWithComposerCapableFieldsAddsProvidesPackagesWithoutExtEmConf(): void
    {
        $extKey = $this->createFakeExtension();
        $rootPath = $this->fakedExtensions[$extKey]['packagePath'];
        file_put_contents($rootPath . 'composer.json', json_encode([
            'name' => 'vendor/' . $extKey,
            'type' => 'typo3-cms-extension',
        ]));
        $subject = $this->getAccessibleMock(
            FileHandlingUtility::class,
            null,
            [
                $this->createMock(PackageManager::class),
                new EmConfUtility(),
                $this->createMock(OpcodeCacheService::class),
                $this->createMock(ZipService::class),
                $this->createMock(LanguageServiceFactory::class),
            ]
        );
        $subject->_call('enrichComposerJsonWithComposerCapableFields', $extKey, $rootPath);
        $composerJson = json_decode(file_get_contents($rootPath . 'composer.json'), true);
        self::assertArrayNotHasKey('version', $composerJson);
        self::assertArrayNotHasKey('version', $composerJson['extra']['typo3/cms']);
        self::assertSame([], (array)$composerJson['extra']['typo3/cms']['Package']['providesPackages']);
    }

    #[Test]
    public function enrichComposerJsonWithComposerCapableFieldsUsesProvidedVersion(): void
    {
        $extKey = $this->createFakeExtension();
        $rootPath = $this->fakedExtensions[$extKey]['packagePath'];
        file_put_contents($rootPath . 'composer.json', json_encode([
            'name' => 'vendor/' . $extKey,
            'type' => 'typo3-cms-extension',
        ]));
        $subject = $this->getAccessibleMock(
            FileHandlingUtility::class,
            null,
            [
                $this->createMock(PackageManager::class),
                new EmConfUtility(),
                $this->createMock(OpcodeCacheService::class),
                $this->createMock(ZipService::class),
                $this->createMock(LanguageServiceFactory::class),
            ]
        );
        $subject->_call('enrichComposerJsonWithComposerCapableFields', $extKey, $rootPath, '5.0.0');
        $composerJson = json_decode(file_get_contents($rootPath . 'composer.json'), true);
        self::assertArrayNotHasKey('version', $composerJson);
        self::assertSame('5.0.0', $composerJson['extra']['typo3/cms']['version']);
        self::assertSame([], (array)$composerJson['extra']['typo3/cms']['Package']['providesPackages']);
    }

    #[Test]
    public function enrichComposerJsonWithComposerCapableFieldsDerivesProvidesPackagesFromRequirements(): void
    {
        $extKey = $this->createFakeExtension();
        $rootPath = $this->fakedExtensions[$extKey]['packagePath'];
        file_put_contents($rootPath . 'composer.json', json_encode([
            'name' => 'vendor/' . $extKey,
            'type' => 'typo3-cms-extension',
            'require' => [
                'typo3/cms-core' => '^14.4',
                'php' => '^8.2',
                'vendor/bundled-library' => '^2.0',
            ],
            'suggest' => [
                'vendor/optional-library' => 'For additional features',
            ],
        ]));
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('isFrameworkPackage')->willReturnCallback(
            static fn(string $packageName): bool => $packageName === 'typo3/cms-core'
        );
        $packageManager->method('isComposerDependency')->willReturnCallback(
            static fn(string $packageName): bool => $packageName === 'php'
        );
        $subject = $this->getAccessibleMock(
            FileHandlingUtility::class,
            null,
            [
                $packageManager,
                new EmConfUtility(),
                $this->createMock(OpcodeCacheService::class),
                $this->createMock(ZipService::class),
                $this->createMock(LanguageServiceFactory::class),
            ]
        );
        $subject->_call('enrichComposerJsonWithComposerCapableFields', $extKey, $rootPath, '5.0.0');
        $composerJson = json_decode(file_get_contents($rootPath . 'composer.json'), true);
        self::assertSame(
            ['vendor/bundled-library' => '', 'vendor/optional-library' => ''],
            $composerJson['extra']['typo3/cms']['Package']['providesPackages']
        );
    }

    #[Test]
    public function writeEmConfWritesEmConfFile(): void
    {
        $extKey = $this->createFakeExtension();
        $emConfData = [
            'title' => 'Plugin cache engine',
            'description' => 'Provides an interface to cache plugin content elements based on 4.3 caching framework',
            'category' => 'Frontend',
        ];
        $rootPath = $this->fakedExtensions[$extKey]['packagePath'];
        $subject = $this->getAccessibleMock(
            FileHandlingUtility::class,
            ['makeAndClearExtensionDir'],
            [
                $this->createMock(PackageManager::class),
                new EmConfUtility(),
                $this->createMock(OpcodeCacheService::class),
                $this->createMock(ZipService::class),
                $this->createMock(LanguageServiceFactory::class),
            ]
        );
        $subject->_call('writeEmConfToFile', $extKey, $emConfData, $rootPath);
        self::assertFileExists($rootPath . 'ext_emconf.php');
    }
}
