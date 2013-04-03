<?php
namespace TYPO3\CMS\Core\Tests\Unit\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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

/**
 * Testcase
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class BootstrapTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/////////////////////////////////////////
	// Tests concerning loadCachedTCA
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function loadCachedTcaRequiresCacheFileIfCacheEntryExists() {
		/** @var $bootstrapInstance \TYPO3\CMS\Core\Core\Bootstrap|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$bootstrapInstance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Core\\Bootstrap',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\CacheManager',
			array('getCache')
		);
		$GLOBALS['typo3CacheManager']
			->expects($this->any())
			->method('getCache')
			->will($this->returnValue($mockCache));
		$mockCache
			->expects($this->any())
			->method('has')
			->will($this->returnValue(TRUE));
		$mockCache
			->expects($this->once())
			->method('requireOnce');
		$bootstrapInstance->loadCachedTca();
	}

	/**
	 * @test
	 */
	public function loadCachedTcaSetsCacheEntryIfNoCacheEntryExists() {
		/** @var $bootstrapInstance \TYPO3\CMS\Core\Core\Bootstrap|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$bootstrapInstance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Core\\Bootstrap',
			array('loadExtensionTables'),
			array(),
			'',
			FALSE
		);
		$mockCache = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['typo3CacheManager'] = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\CacheManager',
			array('getCache')
		);
		$GLOBALS['typo3CacheManager']
			->expects($this->any())
			->method('getCache')
			->will($this->returnValue($mockCache));
		$mockCache
			->expects($this->any())
			->method('has')
			->will($this->returnValue(FALSE));
		$mockCache
			->expects($this->once())
			->method('set');
		$bootstrapInstance->loadCachedTca();
	}

	/**
	 * @test
	 */
	public function checkUtf8DatabaseSettingsInitializesForceCharset() {

		$bootstrapInstance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Core\\Bootstrap',
			array('loadExtensionTables'),
			array(),
			'',
			FALSE
		);

		unset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');
		$this->assertArrayNotHasKey('forceCharset', $GLOBALS['TYPO3_CONF_VARS']['BE']);

		$GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] = 'utf-8';
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');
		$this->assertArrayNotHasKey('forceCharset', $GLOBALS['TYPO3_CONF_VARS']['BE']);

		// we can not test this because at the moment since the script dies, exceptions would be better
		/*$GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] = 'invalidValue';
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');*/
	}

	/**
	 * @test
	 */
	public function checkUtf8DatabaseSettingsInitializesSetDBinit() {

		$bootstrapInstance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Core\\Bootstrap',
			array('loadExtensionTables'),
			array(),
			'',
			FALSE
		);

		$defaultValue = LF . 'SET NAMES utf8;';

		// non-existing setting gets initialized with the default value
		unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']);
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');
		$this->assertEquals($defaultValue, $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']);

		// -1 value is overwritten with default value
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] = '-1';
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');
		$this->assertEquals($defaultValue, $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']);

		// string that does not contain "SET NAMES" is appendet
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] = 'someTotallyUselessStatement;';
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');
		$this->assertEquals('someTotallyUselessStatement;' . $defaultValue, $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']);

		// string that matches regex is not touched
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] = 'SET NAMES "utf8";';
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');
		$this->assertEquals('SET NAMES "utf8";', $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']);

		// another string that matches regex is not touched
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] = 'myStupidString; SET NAMES "utf8"; myOtherStupidString;';
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');
		$this->assertEquals($GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'], 'myStupidString; SET NAMES "utf8"; myOtherStupidString;');

		// we can not test this because at the moment since the script dies, exceptions would be better
		/*$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] = 'SET NAMES "latin1";';
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');*/
	}
}
?>