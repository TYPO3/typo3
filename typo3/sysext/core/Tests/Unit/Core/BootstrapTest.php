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
}
?>