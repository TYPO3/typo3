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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class InstallUtilityTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private string $extensionKey = 'dummy';
    private array $extensionData = [];
    private array $fakedExtensions = [];
    private InstallUtility&MockObject&AccessibleObjectInterface $installMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extensionData = [
            'key' => $this->extensionKey,
            'packagePath' => '',
        ];
        $this->installMock = $this->getAccessibleMock(
            InstallUtility::class,
            [
                'loadExtension',
                'unloadExtension',
                'updateDatabase',
                'importStaticSqlFile',
                'importT3DFile',
                'reloadCaches',
                'processExtensionSetup',
                'saveDefaultConfiguration',
                'getExtensionArray',
                'enrichExtensionWithDetails',
                'importInitialFiles',
            ]
        );
        $eventDispatcher = new NoopEventDispatcher();
        $this->installMock->injectEventDispatcher($eventDispatcher);
        $this->installMock->injectBootService($this->createMock(BootService::class));
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('get')->with(EventDispatcherInterface::class)->willReturn($eventDispatcher);
        $bootServiceMock = $this->createMock(BootService::class);
        $bootServiceMock->method('getContainer')->with(false)->willReturn($containerMock);
        $bootServiceMock->method('makeCurrent')->with(self::anything())->willReturn([]);
        $this->installMock->injectBootService($bootServiceMock);
        $this->installMock
            ->method('getExtensionArray')
            ->with($this->extensionKey)
            ->willReturnCallback($this->getExtensionData(...));
        $this->installMock
            ->method('enrichExtensionWithDetails')
            ->with($this->extensionKey)
            ->willReturnCallback($this->getExtensionData(...));

        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheManagerMock->method('getCache')->with('core')->willReturn(new NullFrontend('core'));
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);
    }

    protected function tearDown(): void
    {
        foreach ($this->fakedExtensions as $fakeExtkey => $fakeExtension) {
            $this->testFilesToDelete[] = Environment::getVarPath() . '/tests/' . $fakeExtkey;
        }
        parent::tearDown();
    }

    public function getExtensionData(): array
    {
        return $this->extensionData;
    }

    /**
     * Creates a fake extension inside typo3temp/.
     * No configuration is created, just the folder.
     *
     * @return string The extension key
     */
    private function createFakeExtension(): string
    {
        $extKey = strtolower(StringUtility::getUniqueId('testing'));
        $absExtPath = Environment::getVarPath() . '/tests/' . $extKey;
        GeneralUtility::mkdir_deep($absExtPath);
        $this->fakedExtensions[$extKey] = [
            'packagePath' => $absExtPath,
        ];
        return $extKey;
    }

    #[Test]
    public function installCallsUpdateDatabase(): void
    {
        $this->installMock->expects(self::once())->method('updateDatabase');

        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->install($this->extensionKey);
    }

    #[Test]
    public function installCallsLoadExtension(): void
    {
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->expects(self::once())->method('loadExtension');
        $this->installMock->install($this->extensionKey);
    }

    #[Test]
    public function installCallsFlushCaches(): void
    {
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects(self::once())->method('flushCaches');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->install($this->extensionKey);
    }

    #[Test]
    public function installCallsReloadCaches(): void
    {
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->expects(self::once())->method('reloadCaches');
        $this->installMock->install($this->extensionKey);
    }

    #[Test]
    public function installCallsSaveDefaultConfigurationWithExtensionKey(): void
    {
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->expects(self::once())->method('saveDefaultConfiguration')->with($this->extensionKey);
        $this->installMock->install($this->extensionKey);
    }

    public static function importT3DFileDoesNotImportFileIfAlreadyImportedDataProvider(): array
    {
        return [
            'Import T3D file when T3D was imported before extension to XML' => [
                'data.t3d',
                'dataImported',
                'data.t3d',
            ],
            'Import T3D file when a file was imported after extension to XML' => [
                'data.t3d',
                'data.t3d',
                'dataImported',
            ],
            'Import XML file when T3D was imported before extension to XML' => [
                'data.xml',
                'dataImported',
                'data.t3d',
            ],
            'Import XML file when a file was imported after extension to XML' => [
                'data.xml',
                'data.t3d',
                'dataImported',
            ],
        ];
    }

    #[DataProvider('importT3DFileDoesNotImportFileIfAlreadyImportedDataProvider')]
    #[Test]
    public function importT3DFileDoesNotImportFileIfAlreadyImported(string $fileName, string $registryNameReturnsFalse, string $registryNameReturnsTrue): void
    {
        $extKey = $this->createFakeExtension();
        $absPath = $this->fakedExtensions[$extKey]['packagePath'];
        $relPath = PathUtility::stripPathSitePrefix($absPath);
        GeneralUtility::mkdir($absPath . 'Initialisation');
        file_put_contents($absPath . 'Initialisation/' . $fileName, 'DUMMY');
        $registryMock = $this->getMockBuilder(Registry::class)
            ->onlyMethods(['get', 'set'])
            ->getMock();
        $registryMock
            ->method('get')
            ->willReturnMap(
                [
                    ['extensionDataImport', $relPath . 'Initialisation/' . $registryNameReturnsFalse, null, false],
                    ['extensionDataImport', $relPath . 'Initialisation/' . $registryNameReturnsTrue, null, true],
                ]
            );
        $installMock = $this->getAccessibleMock(
            InstallUtility::class,
            null,
            [],
            '',
            false
        );
        $installMock->_set('registry', $registryMock);
        $installMock->_call('importT3DFile', $extKey, $this->fakedExtensions[$extKey]['packagePath']);
    }
}
