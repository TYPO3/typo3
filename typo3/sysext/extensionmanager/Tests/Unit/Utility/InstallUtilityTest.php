<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Susanne Moog, <susanne.moog@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase
 *
 */
class InstallUtilityTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {
	/**
	 * @var string
	 */
	protected $extensionKey;

	/**
	 * @var array
	 */
	protected $extensionData = array();

	/**
	 * @var array List of created fake extensions to be deleted in tearDown() again
	 */
	protected $fakedExtensions = array();

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Utility\InstallUtility
	 */
	protected $installMock;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->extensionKey = 'dummy';
		$this->extensionData = array(
			'key' => $this->extensionKey
		);
		$this->installMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility', array(
			'loadExtension',
			'unloadExtension',
			'processDatabaseUpdates',
			'reloadCaches',
			'processCachingFrameworkUpdates',
			'saveDefaultConfiguration',
			'enrichExtensionWithDetails',
			'ensureConfiguredDirectoriesExist',
		));
		$this->installMock->expects($this->any())
				->method('enrichExtensionWithDetails')
				->with($this->extensionKey)
				->will($this->returnCallback(array($this, 'getExtensionData')));
	}

	/**
	 * @return array
	 */
	public function getExtensionData() {
		return $this->extensionData;
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		foreach ($this->fakedExtensions as $extension => $dummy) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::rmdir(PATH_site . 'typo3temp/' . $extension, TRUE);
		}
	}

	/**
	 * Creates a fake extension inside typo3temp/. No configuration is created,
	 * just the folder
	 *
	 * @return string The extension key
	 */
	protected function createFakeExtension() {
		$extKey = strtolower(uniqid('testing'));
		$absExtPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$relPath = 'typo3temp/' . $extKey . '/';
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($absExtPath);
		$this->fakedExtensions[$extKey] = array(
			'siteRelPath' => $relPath
		);
		return $extKey;
	}

	/**
	 * @test
	 */
	public function installCallsProcessDatabaseUpdates() {
		$this->installMock->expects($this->once())
				->method('processDatabaseUpdates')
				->with($this->extensionData);

		$this->installMock->install($this->extensionKey);
	}

	/**
	 * @test
	 */
	public function installCallsProcessCachingFrameworkUpdates() {
		$this->installMock->expects($this->once())
			->method('processCachingFrameworkUpdates');

		$this->installMock->install($this->extensionKey);
	}

	/**
	 * @test
	 */
	public function installCallsLoadExtenion() {
		$this->installMock->expects($this->once())->method('loadExtension');
		$this->installMock->install($this->extensionKey);
	}

	/**
	 * @test
	 */
	public function installCallsFlushCachesIfClearCacheOnLoadIsSet() {
		$this->extensionData['clearcacheonload'] = TRUE;
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager');
		$GLOBALS['typo3CacheManager']->expects($this->once())->method('flushCaches');
		$this->installMock->install($this->extensionKey);
	}

	/**
	 * @test
	 */
	public function installationOfAnExtensionWillCallEnsureThatDirectoriesExist() {
		$this->installMock->expects($this->once())->method('ensureConfiguredDirectoriesExist');
		$this->installMock->install($this->extensionKey);
	}

	/**
	 * @test
	 */
	public function installCallsReloadCaches() {
		$this->installMock->expects($this->once())->method('reloadCaches');
		$this->installMock->install('dummy');
	}

	/**
	 * @test
	 */
	public function installCallsSaveDefaultConfigurationWithExtensionKey() {
		$this->installMock->expects($this->once())->method('saveDefaultConfiguration')->with('dummy');
		$this->installMock->install('dummy');
	}

	/**
	 * @test
	 */
	public function uninstallCallsUnloadExtension() {
		$this->installMock->expects($this->once())->method('unloadExtension');
		$this->installMock->uninstall($this->extensionKey);
	}

	/**
	 * @test
	 */
	public function processDatabaseUpdatesCallsUpdateDbWithExtTablesSql() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$extTablesFile = $extPath . 'ext_tables.sql';
		$fileContent = 'DUMMY TEXT TO COMPARE';
		file_put_contents($extTablesFile, $fileContent);
		$installMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility', array('updateDbWithExtTablesSql'));
		$installMock->expects($this->once())->method('updateDbWithExtTablesSql')->with($this->stringStartsWith($fileContent));
		$installMock->processDatabaseUpdates($this->fakedExtensions[$extKey]);
	}

	/**
	 * @test
	 */
	public function processDatabaseUpdatesCallsImportStaticSql() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$extTablesFile = $extPath . 'ext_tables_static+adt.sql';
		$fileContent = 'DUMMY TEXT TO COMPARE';
		file_put_contents($extTablesFile, $fileContent);
		$installMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility', array('importStaticSql'));
		$installMock->expects($this->once())->method('importStaticSql')->with($fileContent);
		$installMock->processDatabaseUpdates($this->fakedExtensions[$extKey]);
	}

	/**
	 * @test
	 */
	public function InstallCallsUpdateDbWithCachingFrameworkTables() {
		$extKey = $this->createFakeExtension();
		$installMock = $this->getMock(
			'TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility',
			array(
				'enrichExtensionWithDetails',
				'ensureConfiguredDirectoriesExist',
				'updateDbWithExtTablesSql'
			)
		);
		$installMock->expects($this->any())
			->method('enrichExtensionWithDetails')
			->with($extKey)
			->will($this->returnValue(array('key' => $extKey)));
		$installMock->expects($this->at(2))
			->method('updateDbWithExtTablesSql')
			->with($this->stringContains('CREATE TABLE cf_cache_hash'));

		$installMock->install($extKey);
	}
}
?>