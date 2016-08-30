<?php
namespace TYPO3\CMS\Install\Tests\Unit\Controller\Action\Ajax;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class ExtensionCompatibilityTesterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Package\UnitTestPackageManager A backup of unit test package manager
     */
    protected $backupPackageManager = null;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        // Package manager is mocked in some tests. Backup the original one here to re-inject it to
        // ExtensionManagementUtility in tearDown() again. makeInstance() is allowed to be used here
        // since the PackageManager is registered as singleton by bootstrap.
        $this->backupPackageManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
    }

    /**
     * Tear down
     *
     * @return void
     */
    protected function tearDown()
    {
        ExtensionManagementUtility::setPackageManager($this->backupPackageManager);
        if (file_exists(PATH_site . 'typo3temp/ExtensionCompatibilityTester.txt')) {
            unlink(PATH_site . 'typo3temp/ExtensionCompatibilityTester.txt');
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getExtensionsToLoadGetsExtensionsWithoutExcluded()
    {
        $GLOBALS['TYPO3_LOADED_EXT'] = [
            'news' => '',
            'info' => ''
        ];
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['getExtensionsToExclude'], []);
        $extensionCompatibilityTesterMock->expects($this->once())->method('getExtensionsToExclude')->will($this->returnValue(['info']));
        $result = $extensionCompatibilityTesterMock->_call('getExtensionsToLoad');
        $this->assertEquals(['news' => ''], $result);
    }

    /**
     * @test
     */
    public function executeActionReturnsStringOkIfAllIsWell()
    {
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['getExtensionsToLoad', 'tryToLoadExtLocalconfAndExtTablesOfExtensions', 'deleteProtocolFile'], []);
        $extensionCompatibilityTesterMock->expects($this->once())->method('getExtensionsToLoad')->will($this->returnValue([]));
        $result = $extensionCompatibilityTesterMock->_call('executeAction');
        $this->assertEquals('OK', $result);
    }

    /**
     * @test
     */
    public function executeActionCallsGetExtensionsToLoad()
    {
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['getExtensionsToLoad', 'deleteProtocolFile', 'tryToLoadExtLocalconfAndExtTablesOfExtensions'], []);
        $extensionCompatibilityTesterMock->expects($this->once())->method('getExtensionsToLoad')->will($this->returnValue([]));
        $extensionCompatibilityTesterMock->expects($this->once())->method('getExtensionsToLoad');
        $extensionCompatibilityTesterMock->_call('executeAction');
    }

    /**
     * @test
     */
    public function executeActionCallsLoadExtensions()
    {
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['tryToLoadExtLocalconfAndExtTablesOfExtensions', 'getExtensionsToLoad', 'deleteProtocolFile'], []);
        $extensionCompatibilityTesterMock->expects($this->once())->method('getExtensionsToLoad')->will($this->returnValue([]));
        $extensionCompatibilityTesterMock->expects($this->once())->method('tryToLoadExtLocalconfAndExtTablesOfExtensions');
        $extensionCompatibilityTesterMock->_call('executeAction');
    }

    /**
     * @test
     */
    public function executeActionCallsDeleteProtocolFileIfForceCheckIsSet()
    {
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['tryToLoadExtLocalconfAndExtTablesOfExtensions', 'getExtensionsToLoad', 'deleteProtocolFile'], []);
        $extensionCompatibilityTesterMock->expects($this->once())->method('getExtensionsToLoad')->will($this->returnValue([]));
        $_GET['install']['extensionCompatibilityTester']['forceCheck'] = 1;
        $extensionCompatibilityTesterMock->expects($this->once())->method('deleteProtocolFile');
        $extensionCompatibilityTesterMock->_call('executeAction');
        unset($_GET['install']['extensionCompatibilityTester']['forceCheck']);
    }

    /**
     * @test
     */
    public function deleteProtocolFileDeletesFile()
    {
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['dummy'], []);
        GeneralUtility::writeFile(PATH_site . 'typo3temp/ExtensionCompatibilityTester.txt', 'foobar');
        $extensionCompatibilityTesterMock->_call('deleteProtocolFile');
        $this->assertFalse(file_exists(PATH_site . 'typo3temp/ExtensionCompatibilityTester.txt'));
    }

    /**
     * @test
     */
    public function tryToLoadExtLocalconfAndExtTablesOfExtensionsCallsLoadExtTablesForExtension()
    {
        // tryToLoadExtLocalconfAndExtTablesOfExtensions() triggers a call to ExtensionManagementUtility::loadBaseTca
        // that works on our UnitTestPackageManager to do things. This package manager needs to be mocked here to
        // not trigger unwanted side effects.
        $packageManager = $this->getMock(\TYPO3\CMS\Core\Package\PackageManager::class, [], [], '', false);
        $packageManager->expects($this->any())->method('getActivePackages')->will($this->returnValue([]));
        ExtensionManagementUtility::setPackageManager($packageManager);

        $extension = [
            'demo1' => [
                'type' => 'L',
                'ext_tables.php' => PATH_typo3 . 'sysext/install/Tests/Unit/Controller/Action/Ajax/Fixtures/demo1/ext_tables.php'
            ]
        ];
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['loadExtLocalconfForExtension', 'writeCurrentExtensionToFile', 'loadExtTablesForExtension', 'removeCurrentExtensionFromFile'], []);
        $extensionCompatibilityTesterMock->expects($this->atLeastOnce())->method('loadExtTablesForExtension');
        $extensionCompatibilityTesterMock->_call('tryToLoadExtLocalconfAndExtTablesOfExtensions', $extension);
    }

    /**
     * @test
     */
    public function tryToLoadExtLocalconfAndExtTablesOfExtensionsCallsLoadExtLocalconfForExtension()
    {
        // tryToLoadExtLocalconfAndExtTablesOfExtensions() triggers a call to ExtensionManagementUtility::loadBaseTca
        // that works on our UnitTestPackageManager to do things. This package manager needs to be mocked here to
        // not trigger unwanted side effects.
        $packageManager = $this->getMock(\TYPO3\CMS\Core\Package\PackageManager::class, [], [], '', false);
        $packageManager->expects($this->any())->method('getActivePackages')->will($this->returnValue([]));
        ExtensionManagementUtility::setPackageManager($packageManager);

        $extension = [
            'demo1' => [
                'type' => 'L',
                'ext_localconf.php' => PATH_typo3 . 'sysext/install/Tests/Unit/Controller/Action/Ajax/Fixtures/demo1/ext_localconf.php'
            ]
        ];
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['loadExtLocalconfForExtension', 'writeCurrentExtensionToFile', 'loadExtTablesForExtension', 'removeCurrentExtensionFromFile'], []);
        $extensionCompatibilityTesterMock->expects($this->atLeastOnce())->method('loadExtLocalconfForExtension');
        $extensionCompatibilityTesterMock->_call('tryToLoadExtLocalconfAndExtTablesOfExtensions', $extension);
    }

    /**
     * @test
     */
    public function loadExtLocalconfForExtensionIncludesExtLocalconfPhp()
    {
        $extension = [
            'demo1' => [
                'type' => 'L',
                'ext_localconf.php' => PATH_typo3 . 'sysext/install/Tests/Unit/Controller/Action/Ajax/Fixtures/demo1/ext_localconf.php'
            ]
        ];
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['dummy'], []);
        $extensionCompatibilityTesterMock->_call('loadExtLocalconfForExtension', 'demo1', $extension['demo1']);
        $this->assertArrayHasKey('demo1_executed', $GLOBALS);
        $this->assertEquals('foobaz', $GLOBALS['demo1_executed']);
        unset($GLOBALS['demo1_executed']);
    }

    /**
     * @test
     */
    public function tryToLoadExtLocalconfAndExtTablesOfExtensionsCallsWriteCurrentExtensionToFile()
    {
        // tryToLoadExtLocalconfAndExtTablesOfExtensions() triggers a call to ExtensionManagementUtility::loadBaseTca
        // that works on our UnitTestPackageManager to do things. This package manager needs to be mocked here to
        // not trigger unwanted side effects.
        $packageManager = $this->getMock(\TYPO3\CMS\Core\Package\PackageManager::class, [], [], '', false);
        $packageManager->expects($this->any())->method('getActivePackages')->will($this->returnValue([]));
        ExtensionManagementUtility::setPackageManager($packageManager);

        $extension = [
            'demo1' => [
                'type' => 'L',
                'ext_tables.php' => PATH_typo3 . 'sysext/install/Tests/Unit/Controller/Action/Ajax/Fixtures/demo1/ext_tables.php'
            ]
        ];
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['loadExtLocalconfForExtension', 'writeCurrentExtensionToFile', 'loadExtTablesForExtension', 'removeCurrentExtensionFromFile'], []);
        $extensionCompatibilityTesterMock->expects($this->atLeastOnce())->method('writeCurrentExtensionToFile')->with('demo1');
        $extensionCompatibilityTesterMock->_call('tryToLoadExtLocalconfAndExtTablesOfExtensions', $extension);
    }

    /**
     * @test
     */
    public function writeCurrentExtensionToFileWritesExtensionKeyToFile()
    {
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['dummy'], []);
        $extensionCompatibilityTesterMock->_call('writeCurrentExtensionToFile', 'demo1');
        $fileContent = file_get_contents($extensionCompatibilityTesterMock->_get('protocolFile'));
        $this->assertEquals('demo1', $fileContent);
    }

    /**
     * @test
     */
    public function getExtensionsToExcludeReturnsArray()
    {
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['dummy'], []);
        $returnValue = $extensionCompatibilityTesterMock->_call('getExtensionsToExclude');
        $this->assertInternalType('array', $returnValue);
    }

    /**
     * removeCurrentExtensionFromFileRemovesGivenExtensionDataProvider
     *
     * @return array
     */
    public function removeCurrentExtensionFromFileRemovesGivenExtensionDataProvider()
    {
        return [
            'first' => [
                'demo1',
                'demo1, demo2, demo3',
                'demo2, demo3'
            ],
            'second' => [
                'demo2',
                'demo1, demo2, demo3',
                'demo1, demo3'
            ],
            'third' => [
                'demo3',
                'demo1, demo2, demo3',
                'demo1, demo2'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider removeCurrentExtensionFromFileRemovesGivenExtensionDataProvider
     */
    public function removeCurrentExtensionFromFileRemovesGivenExtension($extensionToRemove, $extensions, $expectedExtensions)
    {
        $extensionCompatibilityTesterMock = $this->getAccessibleMock(\TYPO3\CMS\Install\Controller\Action\Ajax\ExtensionCompatibilityTester::class, ['dummy'], []);
        GeneralUtility::writeFile($extensionCompatibilityTesterMock->_get('protocolFile'), $extensions);
        $extensionCompatibilityTesterMock->_call('removeCurrentExtensionFromFile', $extensionToRemove);

        $fileContent = file_get_contents($extensionCompatibilityTesterMock->_get('protocolFile'));
        $this->assertEquals($expectedExtensions, $fileContent);
    }
}
