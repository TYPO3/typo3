<?php
namespace TYPO3\CMS\Install\Tests\Unit\Controller\Action\Tool;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Susanne Moog <typo3@susannemoog.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
	 ***************************************************************/
use TYPO3\CMS\Core\Utility;
/**
 * Test case
 */
class LoadExtensionsTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	public function tearDown() {
		if (file_exists(PATH_site . 'typo3temp/LoadExtensions.txt')) {
			unlink(PATH_site . 'typo3temp/LoadExtensions.txt');
		}
	}

	/**
	 * handleCallsInitialize
	 *
	 * @test
	 * @return void
	 */
	public function handleCallsInitialize() {
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('initialize', 'checkLoadedExtensions'), array());
		$loadExtensionsMock->expects($this->once())->method('initialize');
		$loadExtensionsMock->_call('handle');
	}

	/**
	 * handleCallsCheckLoadedExtensions
	 *
	 * @test
	 * @return void
	 */
	public function handleCallsCheckLoadedExtensions() {
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('initialize', 'checkLoadedExtensions'), array());
		$loadExtensionsMock->expects($this->once())->method('checkLoadedExtensions');
		$loadExtensionsMock->_call('handle');
	}

	/**
	 * checkLoadedExtensionsReturnsJsonStringOkIfAllIsWell
	 *
	 * @test
	 * @return void
	 */
	public function checkLoadedExtensionsReturnsJsonStringOkIfAllIsWell() {
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('getExtensionsToLoad', 'tryToLoadExtLocalconfAndExtTablesOfExtensions', 'deleteProtocolFile'), array());
		$loadExtensionsMock->expects($this->once())->method('getExtensionsToLoad')->will($this->returnValue(array()));
		$result = $loadExtensionsMock->_call('checkLoadedExtensions');
		$this->assertEquals('OK', $result);
	}

	/**
	 * checkLoadedExtensionsCallsGetExtensionsToLoad
	 *
	 * @test
	 * @return void
	 */
	public function checkLoadedExtensionsCallsGetExtensionsToLoad() {
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('getExtensionsToLoad', 'deleteProtocolFile', 'tryToLoadExtLocalconfAndExtTablesOfExtensions'), array());
		$loadExtensionsMock->expects($this->once())->method('getExtensionsToLoad')->will($this->returnValue(array()));
		$loadExtensionsMock->expects($this->once())->method('getExtensionsToLoad');
		$loadExtensionsMock->_call('checkLoadedExtensions');
	}

	/**
	 * checkLoadedExtensionsCallsLoadExtensions
	 *
	 * @test
	 * @return void
	 */
	public function checkLoadedExtensionsCallsLoadExtensions() {
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('tryToLoadExtLocalconfAndExtTablesOfExtensions', 'getExtensionsToLoad', 'deleteProtocolFile'), array());
		$loadExtensionsMock->expects($this->once())->method('getExtensionsToLoad')->will($this->returnValue(array()));
		$loadExtensionsMock->expects($this->once())->method('tryToLoadExtLocalconfAndExtTablesOfExtensions');
		$loadExtensionsMock->_call('checkLoadedExtensions');
	}

	/**
	 * checkLoadedExtensionsCallsDeleteProtocolFile
	 *
	 * @test
	 * @return void
	 */
	public function checkLoadedExtensionsCallsDeleteProtocolFileIfForceCheckIsSet() {
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('tryToLoadExtLocalconfAndExtTablesOfExtensions', 'getExtensionsToLoad', 'deleteProtocolFile'), array());
		$loadExtensionsMock->expects($this->once())->method('getExtensionsToLoad')->will($this->returnValue(array()));
		$_GET['install']['loadExtensions']['forceCheck'] = 1;
		$loadExtensionsMock->expects($this->once())->method('deleteProtocolFile');
		$loadExtensionsMock->_call('checkLoadedExtensions');
		unset($_GET['install']['loadExtensions']['forceCheck']);
	}

	/**
	 * deleteProtocolFileDeletesFile
	 *
	 * @test
	 * @return void
	 */
	public function deleteProtocolFileDeletesFile(){
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('dummy'), array());
		Utility\GeneralUtility::writeFile(PATH_site . 'typo3temp/LoadExtensions.txt', 'foobar');
		$loadExtensionsMock->_call('deleteProtocolFile');
		$this->assertFalse(file_exists(PATH_site . 'typo3temp/LoadExtensions.txt'));
	}

	/**
	 * getLoadedExtensionsReturnsArray
	 *
	 * @test
	 * @return void
	 */
	public function getLoadedExtensionsReturnsArray(){
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('dummy'), array());
		$result = $loadExtensionsMock->_call('getExtensionsToLoad');
		$this->assertInternalType('array', $result);
	}

	/**
	 * getLoadedExtensionsReturnsOnlyNonSystemExtensions
	 *
	 * @test
	 * @return void
	 */
	public function getLoadedExtensionsReturnsOnlyLocalExtensions() {
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('dummy'), array());
		$result = $loadExtensionsMock->_call('getExtensionsToLoad');
		foreach($result as $extension) {
			$this->assertEquals($extension['type'], 'L');
		}
	}

	/**
	 * loadExtTablesForExtensionIncludesExtTablesPhp
	 *
	 * @test
	 * @return void
	 */
	public function loadExtTablesForExtensionIncludesExtTablesPhp() {
		$extension = array(
			'demo1' => array(
				'type' => 'L',
				'ext_tables.php' => PATH_typo3 . 'sysext/install/Tests/Unit/Controller/Action/Tool/Fixtures/demo1/ext_tables.php'
			)
		);
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('dummy'), array());
		$loadExtensionsMock->_call('loadExtTablesForExtension', 'demo1', $extension['demo1']);
		$this->assertArrayHasKey('demo1_executed', $GLOBALS);
		$this->assertEquals('foobar', $GLOBALS['demo1_executed']);
		unset($GLOBALS['demo1_executed']);
	}

	/**
	 * tryToLoadExtLocalconfAndExtTablesOfExtensionsCallsLoadExtTablesForExtension
	 *
	 * @test
	 * @return void
	 */
	public function tryToLoadExtLocalconfAndExtTablesOfExtensionsCallsLoadExtTablesForExtension() {
		$extension = array(
			'demo1' => array(
				'type' => 'L',
				'ext_tables.php' => PATH_typo3 . 'sysext/install/Tests/Unit/Controller/Action/Tool/Fixtures/demo1/ext_tables.php'
			)
		);
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('loadExtLocalconfForExtension', 'writeCurrentExtensionToFile', 'loadExtTablesForExtension', 'removeCurrentExtensionFromFile'), array());
		$loadExtensionsMock->expects($this->atLeastOnce())->method('loadExtTablesForExtension');
		$loadExtensionsMock->_call('tryToLoadExtLocalconfAndExtTablesOfExtensions', $extension);
	}

	/**
	 * tryToLoadExtLocalconfAndExtTablesOfExtensionsCallsLoadExtLocalconfForExtension
	 *
	 * @test
	 * @return void
	 */
	public function tryToLoadExtLocalconfAndExtTablesOfExtensionsCallsLoadExtLocalconfForExtension() {
		$extension = array(
			'demo1' => array(
				'type' => 'L',
				'ext_localconf.php' => PATH_typo3 . 'sysext/install/Tests/Unit/Controller/Action/Tool/Fixtures/demo1/ext_localconf.php'
			)
		);
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('loadExtLocalconfForExtension', 'writeCurrentExtensionToFile', 'loadExtTablesForExtension', 'removeCurrentExtensionFromFile'), array());
		$loadExtensionsMock->expects($this->atLeastOnce())->method('loadExtLocalconfForExtension');
		$loadExtensionsMock->_call('tryToLoadExtLocalconfAndExtTablesOfExtensions', $extension);
	}

	/**
	 * loadExtTablesForExtensionIncludesExtTablesPhp
	 *
	 * @test
	 * @return void
	 */
	public function loadExtLocalconfForExtensionIncludesExtLocalconfPhp() {
		$extension = array(
			'demo1' => array(
				'type' => 'L',
				'ext_localconf.php' => PATH_typo3 . 'sysext/install/Tests/Unit/Controller/Action/Tool/Fixtures/demo1/ext_localconf.php'
			)
		);
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('dummy'), array());
		$loadExtensionsMock->_call('loadExtLocalconfForExtension', 'demo1', $extension['demo1']);
		$this->assertArrayHasKey('demo1_executed', $GLOBALS);
		$this->assertEquals('foobaz', $GLOBALS['demo1_executed']);
		unset($GLOBALS['demo1_executed']);
	}

	/**
	 * tryToLoadExtLocalconfAndExtTablesOfExtensionsCallsWriteCurrentExtensionToFile
	 *
	 * @test
	 * @return void
	 */
	public function tryToLoadExtLocalconfAndExtTablesOfExtensionsCallsWriteCurrentExtensionToFile() {
		$extension = array(
			'demo1' => array(
				'type' => 'L',
				'ext_tables.php' => PATH_typo3 . 'sysext/install/Tests/Unit/Controller/Action/Tool/Fixtures/demo1/ext_tables.php'
			)
		);
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('loadExtLocalconfForExtension', 'writeCurrentExtensionToFile', 'loadExtTablesForExtension', 'removeCurrentExtensionFromFile'), array());
		$loadExtensionsMock->expects($this->atLeastOnce())->method('writeCurrentExtensionToFile')->with('demo1');
		$loadExtensionsMock->_call('tryToLoadExtLocalconfAndExtTablesOfExtensions', $extension);
	}


	/**
	 * writeCurrentExtensionToFileWritesExtensionKeyToFile
	 *
	 * @test
	 * @return void
	 */
	public function writeCurrentExtensionToFileWritesExtensionKeyToFile() {
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('dummy'), array());
		$loadExtensionsMock->_call('writeCurrentExtensionToFile', 'demo1');
		$fileContent = file_get_contents(PATH_site . 'typo3temp/LoadExtensions.txt');
		$this->assertEquals('demo1', $fileContent);
	}

	/**
	 * getExtensionsToLoadCallsGetExtensionsToExclude
	 *
	 * @test
	 * @return void
	 */
	public function getExtensionsToLoadCallsGetExtensionsToExclude() {
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('getExtensionsToExclude'), array());
		$loadExtensionsMock->expects($this->once())->method('getExtensionsToExclude');
		$loadExtensionsMock->_call('getExtensionsToLoad');
	}

	/**
	 * getExtensionsToExcludeReturnsArray
	 *
	 * @test
	 * @return void
	 */
	public function getExtensionsToExcludeReturnsArray() {
		$loadExtensionsMock = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\LoadExtensions', array('dummy'), array());
		$returnValue = $loadExtensionsMock->_call('getExtensionsToExclude');
		$this->assertInternalType('array', $returnValue);
	}
}
