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
}
