<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Susanne Moog, <susanne.moog@typo3.org>
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
 * Testcase for the Tx_Extensionmanager_Utility_List class in the TYPO3 Core.
 *
 * @package Extension Manager
 * @subpackage Tests
 */
class Tx_Extensionmanager_Utility_InstallTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	public $extListGlobal;
	public $loadedExtGlobal;
	public $extension;
	public $fakedExtensions;
	/**
	 * @var Tx_Extensionmanager_Utility_Install
	 */
	public $installMock;
	protected $listUtilityMock;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->extension = 'dummy';
		$this->installMock = $this->getAccessibleMock(
			'Tx_Extensionmanager_Utility_Install',
			array(
				'loadExtension',
				'unloadExtension',
				'processDatabaseUpdates',
				'reloadCaches',
				'saveDefaultConfiguration',
				'enrichExtensionWithDetails'
			)
		);
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		foreach ($this->fakedExtensions as $extension => $dummy) {
			t3lib_div::rmdir(PATH_site . 'typo3temp/' . $extension, TRUE);
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
		t3lib_div::mkdir($absExtPath);

		$this->fakedExtensions[$extKey] = array(
			'siteRelPath' => $relPath
		);

		return $extKey;
	}

	/**
	 * @test
	 * @return void
	 */
	public function installCallsProcessDatabaseUpdates() {
		$this->installMock
			->expects($this->once())
			->method('enrichExtensionWithDetails')
			->with($this->extension)
			->will($this->returnValue(array('key' => $this->extension)));
		$this->installMock->expects($this->once())->method('processDatabaseUpdates')->with(array('key' => $this->extension));
		$this->installMock->install($this->extension);
	}
	/**
	 * @test
	 * @return void
	 */
	public function installCallsLoadExtenion() {
		$this->installMock
			->expects($this->once())
			->method('enrichExtensionWithDetails')
			->with($this->extension)
			->will($this->returnValue(array('key' => $this->extension)));
		$this->installMock->expects($this->once())->method('loadExtension');
		$this->installMock->install($this->extension);
	}
	/**
	 * @test
	 * @return void
	 */
	public function installCallsFlushCachesIfClearCacheOnLoadIsSet() {
		$this->installMock
			->expects($this->once())
			->method('enrichExtensionWithDetails')
			->with($this->extension)
			->will($this->returnValue(array('key' => $this->extension, 'clearcacheonload' => TRUE)));
		$backupCacheManager = $GLOBALS['typo3CacheManager'];
		$GLOBALS['typo3CacheManager'] = $this->getMock('t3lib_cache_manager');
		$GLOBALS['typo3CacheManager']->expects($this->once())->method('flushCaches');
		$this->installMock->install($this->extension);
		$GLOBALS['typo3CacheManager'] = $backupCacheManager;
	}

	/**
	 * @test
	 * @return void
	 */
	public function installCallsReloadCaches() {
		$this->installMock
			->expects($this->once())
			->method('enrichExtensionWithDetails')
			->with($this->extension)
			->will($this->returnValue(array('key' => $this->extension)));
		$this->installMock->expects($this->once())->method('reloadCaches');
		$this->installMock->install('dummy');
	}

	/**
	 * @test
	 * @return void
	 */
	public function installCallsSaveDefaultConfigurationWithExtensionKey() {
		$this->installMock
			->expects($this->once())
			->method('enrichExtensionWithDetails')
			->with($this->extension)
			->will($this->returnValue(array('key' => $this->extension)));
		$this->installMock->expects($this->once())->method('saveDefaultConfiguration')->with('dummy');
		$this->installMock->install('dummy');
	}

	/**
	 * @test
	 * @return void
	 */
	public function uninstallCallsUnloadExtension() {
		$this->installMock->expects($this->once())->method('unloadExtension');
		$this->installMock->uninstall('dummy');
	}

	/**
	 * @test
	 * @return void
	 */
	public function processDatabaseUpdatesCallsUpdateDbWithExtTablesSql() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$extTablesFile = $extPath . 'ext_tables.sql';
		$fileContent = 'DUMMY TEXT TO COMPARE';
		file_put_contents($extTablesFile, $fileContent);
		$installMock = $this->getMock('Tx_Extensionmanager_Utility_Install', array('updateDbWithExtTablesSql'));
		$installMock->expects($this->once())->method('updateDbWithExtTablesSql')->with($this->stringStartsWith($fileContent));

		$installMock->processDatabaseUpdates($this->fakedExtensions[$extKey]);
	}

	/**
	 * @test
	 * @return void
	 */
	public function processDatabaseUpdatesCallsUpdateDbWithExtTablesSqlIncludingCachingFrameworkTables() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$extTablesFile = $extPath . 'ext_tables.sql';
		$fileContent = 'DUMMY TEXT TO COMPARE';
		file_put_contents($extTablesFile, $fileContent);
		$installMock = $this->getMock('Tx_Extensionmanager_Utility_Install', array('updateDbWithExtTablesSql'));
		$installMock->expects($this->once())
			->method('updateDbWithExtTablesSql')
			->with($this->stringContains('CREATE TABLE cf_cache_hash')
		);

		$installMock->processDatabaseUpdates($this->fakedExtensions[$extKey]);
	}

	/**
	 * @test
	 * @return void
	 */
	public function processDatabaseUpdatesCallsImportStaticSql() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$extTablesFile = $extPath . 'ext_tables_static+adt.sql';
		$fileContent = 'DUMMY TEXT TO COMPARE';
		file_put_contents($extTablesFile, $fileContent);
		$installMock = $this->getMock('Tx_Extensionmanager_Utility_Install', array('importStaticSql'));
		$installMock->expects($this->once())->method('importStaticSql')->with($fileContent);

		$installMock->processDatabaseUpdates($this->fakedExtensions[$extKey]);
	}

}

?>