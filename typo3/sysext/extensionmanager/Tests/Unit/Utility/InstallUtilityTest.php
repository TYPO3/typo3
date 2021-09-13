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

use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class InstallUtilityTest extends UnitTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @var string
     */
    protected $extensionKey;

    /**
     * @var array
     */
    protected $extensionData = [];

    /**
     * @var array List of created fake extensions to be deleted in tearDown() again
     */
    protected $fakedExtensions = [];

    protected $backupEnvironment = true;

    protected $resetSingletonInstances = true;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|InstallUtility|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $installMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extensionKey = 'dummy';
        $this->extensionData = [
            'key' => $this->extensionKey,
            'packagePath' => '',
        ];
        $this->installMock = $this->getAccessibleMock(
            InstallUtility::class,
            [
                'isLoaded',
                'loadExtension',
                'unloadExtension',
                'updateDatabase',
                'importStaticSqlFile',
                'importT3DFile',
                'reloadCaches',
                'processCachingFrameworkUpdates',
                'processExtensionSetup',
                'saveDefaultConfiguration',
                'getExtensionArray',
                'enrichExtensionWithDetails',
                'importInitialFiles',
            ]
        );
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $this->installMock->injectEventDispatcher($eventDispatcherProphecy->reveal());
        $this->installMock->injectBootService($this->prophesize(BootService::class)->reveal());
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(EventDispatcherInterface::class)->willReturn($eventDispatcherProphecy->reveal());
        $bootServiceProphecy = $this->prophesize(BootService::class);
        $bootServiceProphecy->getContainer(false)->willReturn($containerProphecy->reveal());
        $bootServiceProphecy->makeCurrent(Argument::cetera())->willReturn([]);
        $this->installMock->injectBootService($bootServiceProphecy->reveal());
        $dependencyUtility = $this->getMockBuilder(DependencyUtility::class)->getMock();
        $this->installMock->_set('dependencyUtility', $dependencyUtility);
        $this->installMock->expects(self::any())
            ->method('getExtensionArray')
            ->with($this->extensionKey)
            ->willReturnCallback([$this, 'getExtensionData']);
        $this->installMock->expects(self::any())
            ->method('enrichExtensionWithDetails')
            ->with($this->extensionKey)
            ->willReturnCallback([$this, 'getExtensionData']);

        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('core')->willReturn(new NullFrontend('core'));
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
    }

    protected function tearDown(): void
    {
        foreach ($this->fakedExtensions as $fakeExtkey => $fakeExtension) {
            $this->testFilesToDelete[] = Environment::getVarPath() . '/tests/' . $fakeExtkey;
        }
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function getExtensionData(): array
    {
        return $this->extensionData;
    }

    /**
     * Creates a fake extension inside typo3temp/. No configuration is created,
     * just the folder
     *
     * @return string The extension key
     */
    protected function createFakeExtension(): string
    {
        $extKey = strtolower(StringUtility::getUniqueId('testing'));
        $absExtPath = Environment::getVarPath() . '/tests/' . $extKey;
        GeneralUtility::mkdir($absExtPath);
        $this->fakedExtensions[$extKey] = [
            'packagePath' => $absExtPath
        ];
        return $extKey;
    }

    /**
     * @test
     */
    public function installCallsUpdateDatabase(): void
    {
        $this->installMock->expects(self::once())->method('updateDatabase');

        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects(self::once())->method('flushCachesInGroup');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->install($this->extensionKey);
    }

    /**
     * @test
     */
    public function installCallsLoadExtension(): void
    {
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects(self::once())->method('flushCachesInGroup');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->expects(self::once())->method('loadExtension');
        $this->installMock->install($this->extensionKey);
    }

    /**
     * @test
     */
    public function installCallsFlushCachesIfClearCacheOnLoadIsSet(): void
    {
        $this->extensionData['clearcacheonload'] = true;
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects(self::once())->method('flushCaches');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->install($this->extensionKey);
    }

    /**
     * @test
     */
    public function installCallsFlushCachesIfClearCacheOnLoadCamelCasedIsSet(): void
    {
        $this->extensionData['clearCacheOnLoad'] = true;
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects(self::once())->method('flushCaches');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->install($this->extensionKey);
    }

    /**
     * @test
     */
    public function installCallsReloadCaches(): void
    {
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects(self::once())->method('flushCachesInGroup');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->expects(self::once())->method('reloadCaches');
        $this->installMock->install($this->extensionKey);
    }

    /**
     * @test
     */
    public function installCallsSaveDefaultConfigurationWithExtensionKey(): void
    {
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects(self::once())->method('flushCachesInGroup');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->expects(self::once())->method('saveDefaultConfiguration')->with($this->extensionKey);
        $this->installMock->install($this->extensionKey);
    }

    /**
     * @return array
     */
    public function importT3DFileDoesNotImportFileIfAlreadyImportedDataProvider(): array
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
                'dataImported'
            ],
            'Import XML file when T3D was imported before extension to XML' => [
                'data.xml',
                'dataImported',
                'data.t3d'
            ],
            'Import XML file when a file was imported after extension to XML' => [
                'data.xml',
                'data.t3d',
                'dataImported'
            ]
        ];
    }

    /**
     * @param string $fileName
     * @param string $registryNameReturnsFalse
     * @param string $registryNameReturnsTrue
     * @test
     * @dataProvider importT3DFileDoesNotImportFileIfAlreadyImportedDataProvider
     */
    public function importT3DFileDoesNotImportFileIfAlreadyImported($fileName, $registryNameReturnsFalse, $registryNameReturnsTrue): void
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
            ->expects(self::any())
            ->method('get')
            ->willReturnMap(
                [
                    ['extensionDataImport', $relPath . 'Initialisation/' . $registryNameReturnsFalse, null, false],
                    ['extensionDataImport', $relPath . 'Initialisation/' . $registryNameReturnsTrue, null, true],
                ]
            );
        $installMock = $this->getAccessibleMock(
            InstallUtility::class,
            ['getRegistry', 'getImportExportUtility'],
            [],
            '',
            false
        );
        $dependencyUtility = $this->getMockBuilder(DependencyUtility::class)->getMock();
        $installMock->_set('dependencyUtility', $dependencyUtility);
        $installMock->_set('registry', $registryMock);
        $installMock->expects(self::never())->method('getImportExportUtility');
        $installMock->_call('importT3DFile', $extKey, $this->fakedExtensions[$extKey]['packagePath']);
    }

    /**
     * @test
     */
    public function siteConfigGetsMovedIntoPlace(): void
    {
        // prepare an extension with a shipped site config
        $extKey = $this->createFakeExtension();
        $absPath = $this->fakedExtensions[$extKey]['packagePath'];
        $config = Yaml::dump(['dummy' => true]);
        $siteIdentifier = 'site_identifier';
        GeneralUtility::mkdir_deep($absPath . 'Initialisation/Site/' . $siteIdentifier);
        file_put_contents($absPath . 'Initialisation/Site/' . $siteIdentifier . '/config.yaml', $config);

        GeneralUtility::setSingletonInstance(SiteConfiguration::class, new SiteConfiguration(Environment::getConfigPath() . '/sites', new NullFrontend('core')));

        $packageMock = $this->getMockBuilder(Package::class)
            ->disableOriginalConstructor()
            ->getMock();
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
           ->disableOriginalConstructor()
           ->getMock();
        $packageMock->expects(self::any())
            ->method('getPackagePath')
            ->willReturn($absPath);
        $packageManagerMock->expects(self::any())
            ->method('getPackage')
            ->with(self::equalTo($extKey))
            ->willReturn($packageMock);

        $subject = new InstallUtility();
        $subject->injectEventDispatcher($this->prophesize(EventDispatcherInterface::class)->reveal());
        $subject->injectPackageManager($packageManagerMock);

        $registry = $this->prophesize(Registry::class);
        $registry->get('extensionDataImport', Argument::any())->willReturn('some folder name');
        $registry->get('siteConfigImport', Argument::any())->willReturn(null);
        $registry->set('siteConfigImport', Argument::cetera())->shouldBeCalled();
        $subject->injectRegistry($registry->reveal());

        // provide function result inside test output folder
        $environment = new Environment();
        $configDir = $absPath . 'Result/config';
        if (!file_exists($configDir)) {
            GeneralUtility::mkdir_deep($configDir);
        }
        $environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            $configDir,
            Environment::getCurrentScript(),
            'UNIX'
        );
        $subject->processExtensionSetup($extKey);

        $registry->set('siteConfigImport', $siteIdentifier, 1)->shouldHaveBeenCalled();
        $siteConfigFile = $configDir . '/sites/' . $siteIdentifier . '/config.yaml';
        self::assertFileExists($siteConfigFile);
        self::assertStringEqualsFile($siteConfigFile, $config);
    }

    /**
     * @test
     */
    public function siteConfigGetsNotOverriddenIfExistsAlready(): void
    {
        // prepare an extension with a shipped site config
        $extKey = $this->createFakeExtension();
        $absPath = $this->fakedExtensions[$extKey]['packagePath'];
        $siteIdentifier = 'site_identifier';
        GeneralUtility::mkdir_deep($absPath . 'Initialisation/Site/' . $siteIdentifier);
        file_put_contents($absPath . 'Initialisation/Site/' . $siteIdentifier . '/config.yaml', Yaml::dump(['dummy' => true]));

        // fake an already existing site config in test output folder
        $configDir = $absPath . 'Result/config';
        if (!file_exists($configDir)) {
            GeneralUtility::mkdir_deep($configDir);
        }
        $config = Yaml::dump(['foo' => 'bar']);
        $existingSiteConfig = 'sites/' . $siteIdentifier . '/config.yaml';
        GeneralUtility::mkdir_deep($configDir . '/sites/' . $siteIdentifier);
        file_put_contents($configDir . '/' . $existingSiteConfig, $config);

        GeneralUtility::setSingletonInstance(SiteConfiguration::class, new SiteConfiguration(Environment::getConfigPath() . '/sites', new NullFrontend('core')));
        $packageMock = $this->getMockBuilder(Package::class)
            ->disableOriginalConstructor()
            ->getMock();
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $packageMock->expects(self::any())
            ->method('getPackagePath')
            ->willReturn($absPath);
        $packageManagerMock->expects(self::any())
            ->method('getPackage')
            ->with(self::equalTo($extKey))
            ->willReturn($packageMock);

        $subject = new InstallUtility();
        $subject->injectEventDispatcher($this->prophesize(EventDispatcherInterface::class)->reveal());
        $subject->injectPackageManager($packageManagerMock);

        $registry = $this->prophesize(Registry::class);
        $registry->get('extensionDataImport', Argument::any())->willReturn('some folder name');
        $registry->get('siteConfigImport', Argument::any())->willReturn(null);
        $registry->set('siteConfigImport', Argument::cetera())->shouldNotBeCalled();
        $subject->injectRegistry($registry->reveal());

        $environment = new Environment();

        $environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            $configDir,
            Environment::getCurrentScript(),
            'UNIX'
        );
        $subject->processExtensionSetup($extKey);

        $siteConfigFile = $configDir . '/sites/' . $siteIdentifier . '/config.yaml';
        self::assertFileExists($siteConfigFile);
        self::assertStringEqualsFile($siteConfigFile, $config);
    }
}
