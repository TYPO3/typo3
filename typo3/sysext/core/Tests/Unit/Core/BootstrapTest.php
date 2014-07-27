<?php
namespace TYPO3\CMS\Core\Tests\Unit\Core;

/**
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
		$mockCacheManager = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\CacheManager',
			array('getCache')
		);
		$mockCacheManager
			->expects($this->any())
			->method('getCache')
			->will($this->returnValue($mockCache));
		$mockCache
			->expects($this->any())
			->method('has')
			->will($this->returnValue(TRUE));
		$mockCache
			->expects($this->once())
			->method('get');
		$bootstrapInstance->setEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager', $mockCacheManager);
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
		$mockCacheManager = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\CacheManager',
			array('getCache')
		);
		$mockCacheManager
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
		$bootstrapInstance->setEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager', $mockCacheManager);
		$bootstrapInstance->loadCachedTca();
	}

	/**
	 * Dataprovider for transferDeprecatedCurlSettings
	 *
	 * @return array
	 */
	public function curlProxySettingsToHttpSettingsMapping() {
		return array(
			array('http://proxy:3128/', 'proxy', '3128'),
			array('http://proxy:3128', 'proxy', '3128'),
			array('proxy:3128', 'proxy', '3128'),
			array('https://proxy:3128/', 'proxy', '3128'),
		);
	}

	/**
	 * @test
	 * @dataProvider curlProxySettingsToHttpSettingsMapping
	 */
	public function transferDeprecatedCurlSettings($curlProxyServer, $proxyHost, $proxyPort) {
		/** @var $bootstrapInstance \TYPO3\CMS\Core\Core\Bootstrap|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$bootstrapInstance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Core\\Bootstrap',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer'] = $curlProxyServer;
		$bootstrapInstance->_call('transferDeprecatedCurlSettings');
		$this->assertEquals($proxyHost, $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_host']);
		$this->assertEquals($proxyPort, $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_port']);
	}

	/**
	 * @test
	 */
	public function curlProxyServerDoesNotOverwriteHttpSettings() {
		/** @var $bootstrapInstance \TYPO3\CMS\Core\Core\Bootstrap|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$bootstrapInstance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Core\\Bootstrap',
			array('dummy'),
			array(),
			'',
			FALSE
		);

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer'] = 'http://proxyOld:3128/';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass'] = 'userOld:passOld';
		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_host'] = 'proxyNew';
		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_port'] = '3128';
		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_user'] = 'userNew';
		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_pass'] = 'passNew';

		$bootstrapInstance->_call('transferDeprecatedCurlSettings');
		$this->assertEquals('http://proxyOld:3128/', $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']);
		$this->assertEquals('userOld:passOld', $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']);
		$this->assertEquals('proxyNew', $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_host']);
		$this->assertEquals('3128', $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_port']);
		$this->assertEquals('userNew', $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_user']);
		$this->assertEquals('passNew', $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_pass']);
	}

	/**
	 * @test
	 */
	public function curlAdapterUsedIfCurlUse() {
		/** @var $bootstrapInstance \TYPO3\CMS\Core\Core\Bootstrap|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$bootstrapInstance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Core\\Bootstrap',
			array('dummy'),
			array(),
			'',
			FALSE
		);

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] = TRUE;
		$bootstrapInstance->_call('transferDeprecatedCurlSettings');
		$this->assertEquals('curl', $GLOBALS['TYPO3_CONF_VARS']['HTTP']['adapter']);
	}
}
