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

use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Utility\EmConfUtility;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FileHandlingUtilityTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var array List of created fake extensions to be deleted in tearDown() again
     */
    protected array $fakedExtensions = [];

    /**
     * Creates a fake extension inside typo3temp/. No configuration is created,
     * just the folder
     *
     * @param bool $extkeyOnly
     * @return string The extension key
     */
    protected function createFakeExtension($extkeyOnly = false): string
    {
        $extKey = strtolower(StringUtility::getUniqueId('testing'));
        $absExtPath = Environment::getVarPath() . '/tests/ext-' . $extKey . '/';
        $this->fakedExtensions[$extKey] = [
            'packagePath' => $absExtPath,
        ];
        if ($extkeyOnly === true) {
            return $extKey;
        }
        GeneralUtility::mkdir($absExtPath);
        $this->testFilesToDelete[] = Environment::getVarPath() . '/tests/ext-' . $extKey;
        return $extKey;
    }

    /**
     * @test
     */
    public function makeAndClearExtensionDirRemovesExtensionDirIfAlreadyExists(): void
    {
        $extKey = $this->createFakeExtension();
        /** @var FileHandlingUtility|MockObject|AccessibleObjectInterface $fileHandlerMock */
        $fileHandlerMock = $this->getAccessibleMock(FileHandlingUtility::class, ['removeDirectory', 'addDirectory', 'getExtensionDir'], [], '', false);
        $fileHandlerMock->expects(self::once())
            ->method('removeDirectory')
            ->with(Environment::getVarPath() . '/tests/ext-' . $extKey . '/');
        $fileHandlerMock
            ->method('getExtensionDir')
            ->willReturn(Environment::getVarPath() . '/tests/ext-' . $extKey . '/');
        $fileHandlerMock->_call('makeAndClearExtensionDir', $extKey);
    }

    /**
     * @test
     */
    public function makeAndClearExtensionDirAddsDir(): void
    {
        $extKey = $this->createFakeExtension();
        $fileHandlerMock = $this->getAccessibleMock(FileHandlingUtility::class, ['removeDirectory', 'addDirectory', 'getExtensionDir']);
        $fileHandlerMock->expects(self::once())
            ->method('addDirectory')
            ->with(Environment::getVarPath() . '/tests/ext-' . $extKey . '/');
        $fileHandlerMock
            ->method('getExtensionDir')
            ->willReturn(Environment::getVarPath() . '/tests/ext-' . $extKey . '/');
        $fileHandlerMock->_call('makeAndClearExtensionDir', $extKey);
    }

    /**
     * @test
     */
    public function makeAndClearExtensionDirThrowsExceptionOnInvalidPath(): void
    {
        $this->expectException(ExtensionManagerException::class);
        $this->expectExceptionCode(1337280417);
        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)->getMock();
        $fileHandlerMock = $this->getAccessibleMock(FileHandlingUtility::class, ['removeDirectory', 'addDirectory']);
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->includeLLFile(Argument::any())->willReturn('');
        $languageServiceProphecy->getLL(Argument::any())->willReturn('');
        $languageServiceProphecy->getLLL(Argument::any())->willReturn('');
        $languageServiceFactoryProphecy = $this->prophesize(LanguageServiceFactory::class);
        $languageServiceFactoryProphecy->createFromUserPreferences(Argument::cetera())->willReturn($languageServiceProphecy->reveal());
        $fileHandlerMock->injectLanguageServiceFactory($languageServiceFactoryProphecy->reveal());
        $fileHandlerMock->initializeObject();
        $fileHandlerMock->_call('makeAndClearExtensionDir', 'testing123', 'fakepath');
    }

    /**
     * @test
     */
    public function addDirectoryAddsDirectory(): void
    {
        $extDirPath = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test-extensions-');
        $this->testFilesToDelete[] = $extDirPath;
        $fileHandlerMock = $this->getAccessibleMock(FileHandlingUtility::class, ['dummy']);
        $fileHandlerMock->_call('addDirectory', $extDirPath);
        self::assertDirectoryExists($extDirPath);
    }

    /**
     * @test
     */
    public function removeDirectoryRemovesDirectory(): void
    {
        $extDirPath = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test-extensions-');
        @mkdir($extDirPath);
        $fileHandlerMock = $this->getAccessibleMock(FileHandlingUtility::class, ['dummy']);
        $fileHandlerMock->_call('removeDirectory', $extDirPath);
        self::assertDirectoryDoesNotExist($extDirPath);
    }

    /**
     * @test
     */
    public function removeDirectoryRemovesSymlink(): void
    {
        $absoluteSymlinkPath = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_symlink_');
        $absoluteFilePath = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_file_');
        touch($absoluteFilePath);
        $this->testFilesToDelete[] = $absoluteFilePath;
        symlink($absoluteFilePath, $absoluteSymlinkPath);
        $fileHandler = new FileHandlingUtility();
        $fileHandler->removeDirectory($absoluteSymlinkPath);
        self::assertFalse(is_link($absoluteSymlinkPath));
    }

    /**
     * @test
     */
    public function removeDirectoryDoesNotRemoveContentOfSymlinkedTargetDirectory(): void
    {
        $absoluteSymlinkPath = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_symlink_');
        $absoluteDirectoryPath = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_dir_') . '/';
        $relativeFilePath = StringUtility::getUniqueId('test_file_');

        mkdir($absoluteDirectoryPath);
        touch($absoluteDirectoryPath . $relativeFilePath);

        $this->testFilesToDelete[] = $absoluteDirectoryPath . $relativeFilePath;
        $this->testFilesToDelete[] = $absoluteDirectoryPath;

        symlink($absoluteDirectoryPath, $absoluteSymlinkPath);

        $fileHandler = new FileHandlingUtility();
        $fileHandler->removeDirectory($absoluteSymlinkPath);
        self::assertTrue(is_file($absoluteDirectoryPath . $relativeFilePath));
    }

    /**
     * @test
     */
    public function unpackExtensionFromExtensionDataArrayCreatesTheExtensionDirectory(): void
    {
        $extensionKey = 'test';
        $fileHandlerMock = $this->getAccessibleMock(FileHandlingUtility::class, [
            'makeAndClearExtensionDir',
            'writeEmConfToFile',
            'extractDirectoriesFromExtensionData',
            'createDirectoriesForExtensionFiles',
            'writeExtensionFiles',
            'reloadPackageInformation',
        ]);
        $fileHandlerMock->expects(self::once())->method('extractDirectoriesFromExtensionData')->willReturn([]);
        $fileHandlerMock->expects(self::once())->method('makeAndClearExtensionDir')->with($extensionKey)->willReturn('my_path');
        $fileHandlerMock->unpackExtensionFromExtensionDataArray($extensionKey, []);
    }

    /**
     * @test
     */
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

        $fileHandlerMock = $this->getAccessibleMock(FileHandlingUtility::class, [
            'makeAndClearExtensionDir',
            'writeEmConfToFile',
            'extractDirectoriesFromExtensionData',
            'createDirectoriesForExtensionFiles',
            'writeExtensionFiles',
            'reloadPackageInformation',
        ]);
        $fileHandlerMock->expects(self::once())->method('extractDirectoriesFromExtensionData')->willReturn($directories);
        $fileHandlerMock->expects(self::once())->method('createDirectoriesForExtensionFiles')->with($directories);
        $fileHandlerMock->expects(self::once())->method('makeAndClearExtensionDir')->with($extensionData['extKey'])->willReturn('my_path');
        $fileHandlerMock->expects(self::once())->method('writeExtensionFiles')->with($cleanedFiles);
        $fileHandlerMock->expects(self::once())->method('reloadPackageInformation')->with('test');
        $fileHandlerMock->unpackExtensionFromExtensionDataArray('test', $extensionData);
    }

    /**
     * @test
     */
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
        $fileHandlerMock = $this->getAccessibleMock(FileHandlingUtility::class, ['makeAndClearExtensionDir']);
        $fileHandlerMock->_call('writeExtensionFiles', $files, $rootPath);
        self::assertFileExists($rootPath . 'ChangeLog');
    }

    /**
     * @test
     */
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
        $fileHandlerMock = $this->getAccessibleMock(FileHandlingUtility::class, ['makeAndClearExtensionDir']);
        $extractedDirectories = $fileHandlerMock->_call('extractDirectoriesFromExtensionData', $files);
        $expected = [
            'doc/',
            'mod/doc/',
        ];
        self::assertSame($expected, array_values($extractedDirectories));
    }

    /**
     * @test
     */
    public function createDirectoriesForExtensionFilesCreatesDirectories(): void
    {
        $rootPath = $this->fakedExtensions[$this->createFakeExtension()]['packagePath'];
        $directories = [
            'doc/',
            'mod/doc/',
        ];
        $fileHandlerMock = $this->getAccessibleMock(FileHandlingUtility::class, ['makeAndClearExtensionDir']);
        self::assertDirectoryDoesNotExist($rootPath . 'doc/');
        self::assertDirectoryDoesNotExist($rootPath . 'mod/doc/');
        $fileHandlerMock->_call('createDirectoriesForExtensionFiles', $directories, $rootPath);
        self::assertDirectoryExists($rootPath . 'doc/');
        self::assertDirectoryExists($rootPath . 'mod/doc/');
    }

    /**
     * @test
     */
    public function writeEmConfWritesEmConfFile(): void
    {
        $extKey = $this->createFakeExtension();
        $emConfData = [
            'title' => 'Plugin cache engine',
            'description' => 'Provides an interface to cache plugin content elements based on 4.3 caching framework',
            'category' => 'Frontend',
        ];
        $rootPath = $this->fakedExtensions[$extKey]['packagePath'];
        /** @var FileHandlingUtility|MockObject|AccessibleObjectInterface $fileHandlerMock */
        $fileHandlerMock = $this->getAccessibleMock(FileHandlingUtility::class, ['makeAndClearExtensionDir']);
        $fileHandlerMock->injectEmConfUtility(new EmConfUtility());
        $fileHandlerMock->_call('writeEmConfToFile', $extKey, $emConfData, $rootPath);
        self::assertFileExists($rootPath . 'ext_emconf.php');
    }
}
