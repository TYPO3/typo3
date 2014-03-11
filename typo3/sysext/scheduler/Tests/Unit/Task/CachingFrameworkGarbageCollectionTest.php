<?php
namespace TYPO3\CMS\Scheduler\Tests\Unit\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class CachingFrameworkGarbageCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array
	 */
	protected $singletonInstances = array();

	/**
	 * Set up
	 */
	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
	}

	/**
	 * Reset singleton instances
	 */
	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function executeCallsCollectGarbageOfConfiguredBackend() {
		$cache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend', array(), array(), '', FALSE);
		$cache->expects($this->any())->method('getIdentifier')->will($this->returnValue('cache'));
		$cache->expects($this->atLeastOnce())->method('collectGarbage');
		$mockCacheManager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$mockCacheManager->registerCache($cache);
		GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager', $mockCacheManager);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = array(
			'cache' => array(
				'frontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend',
				'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend',
			)
		);
		/** @var \TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('TYPO3\\CMS\\Scheduler\\Task\\CachingFrameworkGarbageCollectionTask', array('dummy'), array(), '', FALSE);
		$subject->selectedBackends = array('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend');
		$subject->execute();
	}

	/**
	 * @test
	 */
	public function executeDoesNotCallCollectGarbageOfNotConfiguredBackend() {
		$cache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend', array(), array(), '', FALSE);
		$cache->expects($this->any())->method('getIdentifier')->will($this->returnValue('cache'));
		$cache->expects($this->never())->method('collectGarbage');
		$mockCacheManager = new \TYPO3\CMS\Core\Cache\CacheManager();
		$mockCacheManager->registerCache($cache);
		GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager', $mockCacheManager);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = array(
			'cache' => array(
				'frontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend',
				'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend',
			)
		);
		/** @var \TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask|\PHPUnit_Framework_MockObject_MockObject $subject */
		$subject = $this->getMock('TYPO3\\CMS\\Scheduler\\Task\\CachingFrameworkGarbageCollectionTask', array('dummy'), array(), '', FALSE);
		$subject->selectedBackends = array('TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend');
		$subject->execute();
	}

}
