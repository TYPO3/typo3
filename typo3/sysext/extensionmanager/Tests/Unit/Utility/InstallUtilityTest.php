<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class InstallUtilityTest extends UnitTestCase
{
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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|InstallUtility|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $installMock;

    /**
     */
    protected function setUp()
    {
        $this->extensionKey = 'dummy';
        $this->extensionData = [
            'key' => $this->extensionKey
        ];
        $this->installMock = $this->getAccessibleMock(
            InstallUtility::class,
            [
                'isLoaded',
                'loadExtension',
                'unloadExtension',
                'processDatabaseUpdates',
                'processRuntimeDatabaseUpdates',
                'reloadCaches',
                'processCachingFrameworkUpdates',
                'saveDefaultConfiguration',
                'getExtensionArray',
                'enrichExtensionWithDetails',
                'ensureConfiguredDirectoriesExist',
                'importInitialFiles',
                'emitAfterExtensionInstallSignal'
            ],
            [],
            '',
            false
        );
        $dependencyUtility = $this->getMockBuilder(DependencyUtility::class)->getMock();
        $this->installMock->_set('dependencyUtility', $dependencyUtility);
        $this->installMock->expects($this->any())
            ->method('getExtensionArray')
            ->with($this->extensionKey)
            ->will($this->returnCallback([$this, 'getExtensionData']));
        $this->installMock->expects($this->any())
            ->method('enrichExtensionWithDetails')
            ->with($this->extensionKey)
            ->will($this->returnCallback([$this, 'getExtensionData']));
    }

    /**
     * @return array
     */
    public function getExtensionData(): array
    {
        return $this->extensionData;
    }

    /**
     */
    protected function tearDown()
    {
        foreach ($this->fakedExtensions as $fakeExtkey => $fakeExtension) {
            $this->testFilesToDelete[] = Environment::getVarPath() . '/tests/' . $fakeExtkey;
        }
        parent::tearDown();
    }

    /**
     * Creates a fake extension inside typo3temp/. No configuration is created,
     * just the folder
     *
     * @return string The extension key
     */
    protected function createFakeExtension(): string
    {
        $extKey = strtolower($this->getUniqueId('testing'));
        $absExtPath = Environment::getVarPath() . '/tests/' . $extKey;
        $relPath = 'typo3temp/var/tests/' . $extKey . '/';
        GeneralUtility::mkdir($absExtPath);
        $this->fakedExtensions[$extKey] = [
            'siteRelPath' => $relPath
        ];
        return $extKey;
    }

    /**
     * @test
     */
    public function installCallsProcessRuntimeDatabaseUpdates()
    {
        $this->installMock->expects($this->once())
            ->method('processRuntimeDatabaseUpdates')
            ->with($this->extensionKey);

        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects($this->once())->method('flushCachesInGroup');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->install($this->extensionKey);
    }

    /**
     * @test
     */
    public function installCallsLoadExtension()
    {
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects($this->once())->method('flushCachesInGroup');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->expects($this->once())->method('loadExtension');
        $this->installMock->install($this->extensionKey);
    }

    /**
     * @test
     */
    public function installCallsFlushCachesIfClearCacheOnLoadIsSet()
    {
        $this->extensionData['clearcacheonload'] = true;
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects($this->once())->method('flushCaches');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->install($this->extensionKey);
    }

    /**
     * @test
     */
    public function installCallsFlushCachesIfClearCacheOnLoadCamelCasedIsSet()
    {
        $this->extensionData['clearCacheOnLoad'] = true;
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects($this->once())->method('flushCaches');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->install($this->extensionKey);
    }

    /**
     * @test
     */
    public function installationOfAnExtensionWillCallEnsureThatDirectoriesExist()
    {
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects($this->once())->method('flushCachesInGroup');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->expects($this->once())->method('ensureConfiguredDirectoriesExist');
        $this->installMock->install($this->extensionKey);
    }

    /**
     * @test
     */
    public function installCallsReloadCaches()
    {
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects($this->once())->method('flushCachesInGroup');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->expects($this->once())->method('reloadCaches');
        $this->installMock->install('dummy');
    }

    /**
     * @test
     */
    public function installCallsSaveDefaultConfigurationWithExtensionKey()
    {
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)->getMock();
        $cacheManagerMock->expects($this->once())->method('flushCachesInGroup');
        $this->installMock->_set('cacheManager', $cacheManagerMock);
        $this->installMock->expects($this->once())->method('saveDefaultConfiguration')->with('dummy');
        $this->installMock->install('dummy');
    }

    /**
     * @test
     */
    public function uninstallCallsUnloadExtension()
    {
        $this->installMock->expects($this->once())->method('unloadExtension');
        $this->installMock->uninstall($this->extensionKey);
    }

    /**
     * @test
     */
    public function processDatabaseUpdatesCallsUpdateDbWithExtTablesSql()
    {
        $extKey = $this->createFakeExtension();
        $extPath = Environment::getVarPath() . '/tests/' . $extKey . '/';
        $extTablesFile = $extPath . 'ext_tables.sql';
        $fileContent = 'DUMMY TEXT TO COMPARE';
        file_put_contents($extTablesFile, $fileContent);
        $installMock = $this->getAccessibleMock(
            InstallUtility::class,
            ['updateDbWithExtTablesSql', 'importStaticSqlFile', 'importT3DFile'],
            [],
            '',
            false
        );
        $dependencyUtility = $this->getMockBuilder(DependencyUtility::class)->getMock();
        $installMock->_set('dependencyUtility', $dependencyUtility);

        $installMock->expects($this->once())->method('updateDbWithExtTablesSql')->with($this->stringStartsWith($fileContent));
        $installMock->processDatabaseUpdates($this->fakedExtensions[$extKey]);
    }

    /**
     * @test
     */
    public function processDatabaseUpdatesCallsImportStaticSqlFile()
    {
        $extKey = $this->createFakeExtension();
        $extensionSiteRelPath = 'typo3temp/var/tests/' . $extKey . '/';
        $installMock = $this->getAccessibleMock(
            InstallUtility::class,
            ['importStaticSqlFile', 'updateDbWithExtTablesSql', 'importT3DFile'],
            [],
            '',
            false
        );
        $dependencyUtility = $this->getMockBuilder(DependencyUtility::class)->getMock();
        $installMock->_set('dependencyUtility', $dependencyUtility);
        $installMock->expects($this->once())->method('importStaticSqlFile')->with($extensionSiteRelPath);
        $installMock->processDatabaseUpdates($this->fakedExtensions[$extKey]);
    }

    /**
     * @return array
     */
    public function processDatabaseUpdatesCallsImportFileDataProvider(): array
    {
        return [
            'T3D file' => [
                'data.t3d'
            ],
            'XML file' => [
                'data.xml'
            ]
        ];
    }

    /**
     * @param string $fileName
     * @test
     * @dataProvider processDatabaseUpdatesCallsImportFileDataProvider
     */
    public function processDatabaseUpdatesCallsImportFile($fileName)
    {
        $extKey = $this->createFakeExtension();
        $absPath = Environment::getPublicPath() . '/' . $this->fakedExtensions[$extKey]['siteRelPath'];
        GeneralUtility::mkdir($absPath . '/Initialisation');
        file_put_contents($absPath . '/Initialisation/' . $fileName, 'DUMMY');
        $installMock = $this->getAccessibleMock(
            InstallUtility::class,
            ['updateDbWithExtTablesSql', 'importStaticSqlFile', 'importT3DFile'],
            [],
            '',
            false
        );
        $dependencyUtility = $this->getMockBuilder(DependencyUtility::class)->getMock();
        $installMock->_set('dependencyUtility', $dependencyUtility);
        $installMock->expects($this->once())->method('importT3DFile')->with($this->fakedExtensions[$extKey]['siteRelPath']);
        $installMock->processDatabaseUpdates($this->fakedExtensions[$extKey]);
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
    public function importT3DFileDoesNotImportFileIfAlreadyImported($fileName, $registryNameReturnsFalse, $registryNameReturnsTrue)
    {
        $extKey = $this->createFakeExtension();
        $absPath = Environment::getPublicPath() . '/' . $this->fakedExtensions[$extKey]['siteRelPath'];
        GeneralUtility::mkdir($absPath . 'Initialisation');
        file_put_contents($absPath . 'Initialisation/' . $fileName, 'DUMMY');
        $registryMock = $this->getMockBuilder(Registry::class)
            ->setMethods(['get', 'set'])
            ->getMock();
        $registryMock
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(
                [
                    ['extensionDataImport', $this->fakedExtensions[$extKey]['siteRelPath'] . 'Initialisation/' . $registryNameReturnsFalse, null, false],
                    ['extensionDataImport', $this->fakedExtensions[$extKey]['siteRelPath'] . 'Initialisation/' . $registryNameReturnsTrue, null, true],
                ]
            ));
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
        $installMock->expects($this->never())->method('getImportExportUtility');
        $installMock->_call('importT3DFile', $this->fakedExtensions[$extKey]['siteRelPath']);
    }
}
