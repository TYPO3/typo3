<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

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
use TYPO3\CMS\Core\Compatibility\LoadedExtensionsArray;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ExtensionManagementUtilityTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Package\PackageManager
     */
    protected $backUpPackageManager;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->backUpPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        ExtensionManagementUtility::clearExtensionKeyMap();
        ExtensionManagementUtilityAccessibleProxy::setPackageManager($this->backUpPackageManager);
        ExtensionManagementUtilityAccessibleProxy::setCacheManager(null);
        $GLOBALS['TYPO3_LOADED_EXT'] = new LoadedExtensionsArray($this->backUpPackageManager);
        parent::tearDown();
    }

    /**
     * @param string $packageKey
     * @param array $packageMethods
     * @return PackageManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockPackageManagerWithMockPackage($packageKey, $packageMethods = ['getPackagePath', 'getPackageKey'])
    {
        $packagePath = Environment::getVarPath() . '/tests/' . $packageKey . '/';
        GeneralUtility::mkdir_deep($packagePath);
        $this->testFilesToDelete[] = $packagePath;
        $package = $this->getMockBuilder(Package::class)
                ->disableOriginalConstructor()
                ->setMethods($packageMethods)
                ->getMock();
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive', 'getPackage', 'getActivePackages'])
            ->getMock();
        $package->expects($this->any())
                ->method('getPackagePath')
                ->will($this->returnValue($packagePath));
        $package->expects($this->any())
                ->method('getPackageKey')
                ->will($this->returnValue($packageKey));
        $packageManager->expects($this->any())
                ->method('isPackageActive')
                ->will($this->returnValueMap([
                    [null, false],
                    [$packageKey, true]
                ]));
        $packageManager->expects($this->any())
                ->method('getPackage')
                ->with($this->equalTo($packageKey))
                ->will($this->returnValue($package));
        $packageManager->expects($this->any())
                ->method('getActivePackages')
                ->will($this->returnValue([$packageKey => $package]));
        return $packageManager;
    }

    ///////////////////////////////
    // Tests concerning isLoaded
    ///////////////////////////////
    /**
     * @test
     */
    public function isLoadedReturnsFalseIfExtensionIsNotLoadedAndExitIsDisabled()
    {
        $this->assertFalse(ExtensionManagementUtility::isLoaded($this->getUniqueId('foobar'), false));
    }

    ///////////////////////////////
    // Tests concerning extPath
    ///////////////////////////////
    /**
     * @test
     */
    public function extPathThrowsExceptionIfExtensionIsNotLoaded()
    {
        $this->expectException(\BadFunctionCallException::class);
        $this->expectExceptionCode(1365429656);

        $packageName = $this->getUniqueId('foo');
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive'])
            ->getMock();
        $packageManager->expects($this->once())
                ->method('isPackageActive')
                ->with($this->equalTo($packageName))
                ->will($this->returnValue(false));
        ExtensionManagementUtility::setPackageManager($packageManager);
        ExtensionManagementUtility::extPath($packageName);
    }

    /**
     * @test
     */
    public function extPathAppendsScriptNameToPath()
    {
        $package = $this->getMockBuilder(Package::class)
                ->disableOriginalConstructor()
                ->setMethods(['getPackagePath'])
                ->getMock();
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive', 'getPackage'])
            ->getMock();
        $package->expects($this->once())
                ->method('getPackagePath')
                ->will($this->returnValue(Environment::getPublicPath() . '/foo/'));
        $packageManager->expects($this->once())
                ->method('isPackageActive')
                ->with($this->equalTo('foo'))
                ->will($this->returnValue(true));
        $packageManager->expects($this->once())
                ->method('getPackage')
                ->with('foo')
                ->will($this->returnValue($package));
        ExtensionManagementUtility::setPackageManager($packageManager);
        $this->assertSame(Environment::getPublicPath() . '/foo/bar.txt', ExtensionManagementUtility::extPath('foo', 'bar.txt'));
    }

    //////////////////////
    // Utility functions
    //////////////////////
    /**
     * Generates a basic TCA for a given table.
     *
     * @param string $table name of the table, must not be empty
     * @return array generated TCA for the given table, will not be empty
     */
    private function generateTCAForTable($table)
    {
        $tca = [];
        $tca[$table] = [];
        $tca[$table]['columns'] = [
            'fieldA' => [],
            'fieldC' => []
        ];
        $tca[$table]['types'] = [
            'typeA' => ['showitem' => 'fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1'],
            'typeB' => ['showitem' => 'fieldA, fieldB, fieldC;labelC, --palette--;;paletteC, fieldC1, fieldD, fieldD1'],
            'typeC' => ['showitem' => 'fieldC;;paletteD']
        ];
        $tca[$table]['palettes'] = [
            'paletteA' => ['showitem' => 'fieldX, fieldX1, fieldY'],
            'paletteB' => ['showitem' => 'fieldX, fieldX1, fieldY'],
            'paletteC' => ['showitem' => 'fieldX, fieldX1, fieldY'],
            'paletteD' => ['showitem' => 'fieldX, fieldX1, fieldY']
        ];
        return $tca;
    }

    /////////////////////////////////////////////
    // Tests concerning getExtensionKeyByPrefix
    /////////////////////////////////////////////
    /**
     * @test
     * @see ExtensionManagementUtility::getExtensionKeyByPrefix
     */
    public function getExtensionKeyByPrefixForLoadedExtensionWithUnderscoresReturnsExtensionKey()
    {
        ExtensionManagementUtility::clearExtensionKeyMap();
        $uniqueSuffix = $this->getUniqueId('test');
        $extensionKey = 'tt_news' . $uniqueSuffix;
        $extensionPrefix = 'tx_ttnews' . $uniqueSuffix;
        $package = $this->getMockBuilder(Package::class)
                ->disableOriginalConstructor()
                ->setMethods(['getPackageKey'])
                ->getMock();
        $package->expects($this->exactly(2))
                ->method('getPackageKey')
                ->will($this->returnValue($extensionKey));
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['getActivePackages'])
            ->getMock();
        $packageManager->expects($this->once())
                ->method('getActivePackages')
                ->will($this->returnValue([$extensionKey => $package]));
        ExtensionManagementUtility::setPackageManager($packageManager);
        $this->assertEquals($extensionKey, ExtensionManagementUtility::getExtensionKeyByPrefix($extensionPrefix));
    }

    /**
     * @test
     * @see ExtensionManagementUtility::getExtensionKeyByPrefix
     */
    public function getExtensionKeyByPrefixForLoadedExtensionWithoutUnderscoresReturnsExtensionKey()
    {
        ExtensionManagementUtility::clearExtensionKeyMap();
        $uniqueSuffix = $this->getUniqueId('test');
        $extensionKey = 'kickstarter' . $uniqueSuffix;
        $extensionPrefix = 'tx_kickstarter' . $uniqueSuffix;
        $package = $this->getMockBuilder(Package::class)
                ->disableOriginalConstructor()
                ->setMethods(['getPackageKey'])
                ->getMock();
        $package->expects($this->exactly(2))
                ->method('getPackageKey')
                ->will($this->returnValue($extensionKey));
        /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['getActivePackages'])
            ->getMock();
        $packageManager->expects($this->once())
                ->method('getActivePackages')
                ->will($this->returnValue([$extensionKey => $package]));
        ExtensionManagementUtility::setPackageManager($packageManager);
        $this->assertEquals($extensionKey, ExtensionManagementUtility::getExtensionKeyByPrefix($extensionPrefix));
    }

    /**
     * @test
     * @see ExtensionManagementUtility::getExtensionKeyByPrefix
     */
    public function getExtensionKeyByPrefixForNotLoadedExtensionReturnsFalse()
    {
        ExtensionManagementUtility::clearExtensionKeyMap();
        $uniqueSuffix = $this->getUniqueId('test');
        $extensionPrefix = 'tx_unloadedextension' . $uniqueSuffix;
        $this->assertFalse(ExtensionManagementUtility::getExtensionKeyByPrefix($extensionPrefix));
    }

    /////////////////////////////////////////
    // Tests concerning removeCacheFiles
    /////////////////////////////////////////
    /**
     * @test
     */
    public function removeCacheFilesFlushesSystemCaches()
    {
        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMockBuilder(CacheManager::class)
            ->setMethods(['flushCachesInGroup'])
            ->getMock();
        $mockCacheManager->expects($this->once())->method('flushCachesInGroup')->with('system');
        ExtensionManagementUtilityAccessibleProxy::setCacheManager($mockCacheManager);
        ExtensionManagementUtility::removeCacheFiles();
    }
}
