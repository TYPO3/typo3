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
	 * @return void
	 */
	public function checkUtf8DatabaseSettingsDoesNotSetForceCharsetIfItIsNotSet() {

		$bootstrapInstance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Core\\Bootstrap',
			array('dummy'),
			array(),
			'',
			FALSE
		);

		unset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');
		$this->assertArrayNotHasKey('forceCharset', $GLOBALS['TYPO3_CONF_VARS']['BE']);
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkUtf8DatabaseSettingsRemovesForceCharsetIfSetToUtf8() {

		$bootstrapInstance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Core\\Bootstrap',
			array('dummy'),
			array(),
			'',
			FALSE
		);

		$GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] = 'utf-8';
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');
		$this->assertArrayNotHasKey('forceCharset', $GLOBALS['TYPO3_CONF_VARS']['BE']);
	}

	/**
	 * @test
	 * @return void
	 */
	public function checkUtf8DatabaseSettingsInitializesNonexistingSetDbinitWithDefaultValue() {

		$bootstrapInstance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Core\\Bootstrap',
			array('dummy'),
			array(),
			'',
			FALSE
		);

		$defaultValue = LF . 'SET NAMES utf8;';
		unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']);
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');
		$this->assertEquals($defaultValue, $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']);
	}

	/**
	 * @param $value
	 * @param $expected
	 * @test
	 * @dataProvider checkUtf8DatabaseSettingsSetDbinitDataProvider
	 * @return void
	 */
	public function checkUtf8DatabaseSettingsSetDbinit($value, $expected) {
		$bootstrapInstance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Core\\Bootstrap',
			array('dummy'),
			array(),
			'',
			FALSE
		);

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] = $value;
		$bootstrapInstance->_call('checkUtf8DatabaseSettingsOrDie');
		$this->assertEquals($expected, $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']);
	}

	/**
	 * @return array $value, $expected
	 */
	public function checkUtf8DatabaseSettingsSetDbinitDataProvider() {
		return array(
			'-1 is overridden with default value' => array(
				'-1',
				LF . 'SET NAMES utf8;',
			),
			'string without SET NAMES utf-8 is appended with default value' => array(
				'string',
				'string' . LF . 'SET NAMES utf8;',
			),
			'SET NAMES utf8 is not modified' => array(
				'SET NAMES "utf8";',
				'SET NAMES "utf8";',
			),
			'string with SET NAMES utf8 in the middle is not modified' => array(
				'myUselessString; SET NAMES "utf8"; myOtherUselessString;',
				'myUselessString; SET NAMES "utf8"; myOtherUselessString;',
			),
			'string with SET NAMES utf8 and collate is not modified' => array(
				'SET NAMES "utf8 COLLATE utf8_unicode_ci;',
				'SET NAMES "utf8 COLLATE utf8_unicode_ci;',
			),
		);
	}
}
?>